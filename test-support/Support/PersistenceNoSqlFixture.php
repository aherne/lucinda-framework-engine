<?php

namespace Test\Lucinda\Framework\Support;

use Lucinda\Framework\NoSQL\AtomicDriver;
use Lucinda\Framework\NoSQL\MigrationStore;
use Lucinda\Framework\NoSQL\RepresentationStore;
use Lucinda\Framework\NoSQL\SessionStore;
use Lucinda\Framework\NoSQL\VersionedValue;
use Lucinda\Framework\Persistence\Locking\NoSqlLock;
use Lucinda\Framework\Persistence\NoSqlExecutor;
use Lucinda\Framework\Persistence\NoSqlThrottle;
use Lucinda\Framework\Persistence\Queries\ValidationException;
use Lucinda\Framework\Persistence\ThrottleBucket;
use Lucinda\Migration\Status;

/** Exercises persistence policy against the private backend owned by its caller. */
final class PersistenceNoSqlFixture
{
    private AtomicDriver $driver;
    private NoSqlExecutor $executor;
    /** @var list<array{bool, string}> */
    private array $checks = [];

    public function __construct(AtomicDriver $driver)
    {
        $this->driver = $driver;
        $this->executor = new NoSqlExecutor($driver, 'persistence-test:' . bin2hex(random_bytes(12)) . ':');
    }

    private function check(bool $condition, string $message): void
    {
        $this->checks[] = [$condition, $message];
    }

    private function rejects(callable $operation, string $type, string $message): void
    {
        try {
            $operation();
            $this->check(false, $message . ' (no exception)');
        } catch (\Throwable $error) {
            $this->check($error instanceof $type, $message . ' (' . get_class($error) . ')');
        }
    }

    /** Routes one test case; every case gets a distinct logical keyspace. */
    public function run(string $operation): array
    {
        [$group, $method] = explode('.', $operation, 2);
        match ($group) {
            'executor' => $this->executor($method),
            'lock' => $this->lock($method),
            'throttle' => $this->throttle($method),
            'migration' => $this->migration($method),
            'representation' => $this->representation(),
            'session' => $this->session($method),
            default => throw new \RuntimeException('Unknown persistence test group'),
        };
        return $this->checks;
    }

    private function executor(string $method): void
    {
        $key = $this->executor->getKeyspace()->getKey('record', 'one');
        if ($method === 'getKeyspace') {
            $this->check($this->executor->getKeyspace() === $this->executor->getKeyspace(), 'keyspace is retained');
            $this->rejects(fn() => new NoSqlExecutor($this->driver, ''), \InvalidArgumentException::class, 'empty namespace rejected');
            $previous = getenv('PERSISTENCE_NAMESPACE');
            try {
                putenv('PERSISTENCE_NAMESPACE=environment-test:');
                $this->check(
                    str_starts_with((new NoSqlExecutor($this->driver))->getKeyspace()->getKey('domain', 'id'), 'environment-test:'),
                    'omitted namespace loads the environment'
                );
                putenv('PERSISTENCE_NAMESPACE');
                $this->check(str_starts_with((new NoSqlExecutor($this->driver))->getKeyspace()->getKey('domain', 'id'), 'framework:'), 'missing namespace uses the framework default');
            } finally {
                putenv($previous === false ? 'PERSISTENCE_NAMESPACE' : 'PERSISTENCE_NAMESPACE=' . $previous);
            }
        } elseif ($method === 'read') {
            $this->check(!$this->executor->read($key)->exists(), 'missing record is an absent observation');
            $this->driver->set($key, ['id' => 1, 'nullable' => null]);
            $this->check($this->executor->read($key)->getValue() === ['id' => 1, 'nullable' => null], 'record fields are not interpreted by the executor');
            $this->driver->set($key, 'not a record');
            $this->rejects(fn() => $this->executor->read($key), \UnexpectedValueException::class, 'scalar records are rejected');
        } elseif ($method === 'replace') {
            $first = $this->executor->read($key);
            $other = $this->executor->read($key);
            $this->check($this->executor->replace($first, ['id' => 1], 30), 'replacement creates an absent record');
            $this->check(!$this->executor->replace($other, ['id' => 2], 30), 'stale replacement loses without overwriting');
            $this->check($this->executor->read($key)->getValue() === ['id' => 1], 'winning record is retained');
            $this->rejects(fn() => $this->executor->replace($this->executor->read($key), [], -1), \InvalidArgumentException::class, 'negative retention rejected');
        } elseif ($method === 'remove') {
            $this->check(!$this->executor->remove($this->executor->read($key)), 'absent record cannot be removed');
            $this->driver->set($key, ['id' => 1]);
            $old = $this->executor->read($key);
            $this->driver->set($key, ['id' => 2]);
            $this->check(!$this->executor->remove($old), 'stale deletion preserves successor');
            $this->check($this->executor->remove($this->executor->read($key)), 'current record is removed');
        }
    }

    private function lock(string $method): void
    {
        $lock = new NoSqlLock($this->executor, 'lock', 30, 0);
        $key = $this->executor->getKeyspace()->getKey('lock', 'one');
        if ($method === 'acquire') {
            $lock->acquire('one');
            $version = $lock->read()->getVersion();
            $lock->acquire('one');
            $this->check($lock->read()->getVersion() === $version, 'repeated acquisition validates without renewing');
            $other = new NoSqlLock($this->executor, 'lock', 30, 0);
            $this->rejects(fn() => $other->acquire('one'), \RuntimeException::class, 'another owner cannot enter a live lease');
            $lock->acquire('two');
            $other->acquire('one');
            $this->check($other->read()->getValue()['token'] !== null, 'switching identity releases the old lease');
            $other->release();
        } elseif ($method === 'read') {
            $this->rejects(fn() => $lock->read(), \LogicException::class, 'read requires acquired identity');
            $lock->acquire('one');
            $snapshot = $lock->read();
            $this->check($snapshot->getKey() === $key && strlen($snapshot->getValue()['token']) === 48, 'read returns current namespaced ownership');
            $record = $snapshot->getValue();
            $record['lease_expires'] = time() - 1;
            $this->driver->set($key, $record, 60);
            $this->rejects(fn() => $lock->read(), \RuntimeException::class, 'expired owner cannot read as owner');
        } elseif ($method === 'replace') {
            $lock->acquire('one');
            $old = $lock->read();
            $this->check($lock->replace($old, ['payload' => 'first', 'token' => 'forged', 'lease_expires' => 0], 1), 'owned replacement succeeds');
            $record = $lock->read()->getValue();
            $this->check($record['payload'] === 'first' && $record['token'] === $old->getValue()['token'] && $record['lease_expires'] === $old->getValue()['lease_expires'], 'caller cannot replace ownership metadata');
            $this->check(!$lock->replace($old, ['payload' => 'stale'], 30), 'stale snapshot cannot overwrite');
            $forged = new VersionedValue($key, $record, 'forged', 0.0);
            $this->rejects(fn() => $lock->replace($forged, [], 30), \InvalidArgumentException::class, 'forged observation is rejected by the adapter');
        } elseif ($method === 'renew') {
            $this->rejects(fn() => $lock->renew(), \LogicException::class, 'renewal requires ownership');
            $lock->acquire('one');
            $before = $lock->read();
            $lock->replace($before, ['payload' => 'retained'], 60);
            $before = $lock->read();
            $lock->renew();
            $after = $lock->read();
            $this->check($after->getVersion() !== $before->getVersion(), 'renewal changes the logical revision');
            $this->check($after->getValue()['token'] === $before->getValue()['token'] && $after->getValue()['payload'] === 'retained' && $after->getValue()['lease_expires'] >= $before->getValue()['lease_expires'], 'renewal retains ownership and payload');
        } elseif ($method === 'release') {
            $lock->release();
            $lock->acquire('one');
            $lock->release();
            $this->check(!$this->executor->read($key)->exists(), 'standalone lease is conditionally deleted');
            $lock->acquire('one');
            $lock->replace($lock->read(), ['payload' => 'kept'], 60);
            $lock->release();
            $record = $this->executor->read($key)->getValue();
            $this->check($record['payload'] === 'kept' && $record['token'] === null && $record['lease_expires'] === 0, 'release preserves protected payload');
            $lock->acquire('one');
            $record = $lock->read()->getValue();
            $record['lease_expires'] = time() - 1;
            $this->driver->set($key, $record, 60);
            $other = new NoSqlLock($this->executor, 'lock', 30, 0);
            $other->acquire('one');
            $owner = $other->read()->getValue()['token'];
            $lock->release();
            $this->check($other->read()->getValue()['token'] === $owner, 'stale release cannot remove successor ownership');
            $other->release();
        } elseif ($method === 'validation') {
            $this->rejects(fn() => new NoSqlLock($this->executor, '', 30, 0), \InvalidArgumentException::class, 'empty purpose rejected');
            $this->rejects(fn() => new NoSqlLock($this->executor, 'lock', 0, 0), \InvalidArgumentException::class, 'nonpositive lease rejected');
            $this->rejects(fn() => new NoSqlLock($this->executor, 'lock', 30, -1), \InvalidArgumentException::class, 'negative wait rejected');
        }
        $lock->release();
    }

    private function throttle(string $method): void
    {
        $throttle = new NoSqlThrottle($this->executor, 'form', 2, 30);
        $key = $this->executor->getKeyspace()->getKey('throttle', (new ThrottleBucket('form', 'alice', '192.0.2.1'))->getKey());
        if ($method === 'penalize') {
            $throttle->penalize('alice', '192.0.2.1');
            $record = $this->executor->read($key)->getValue();
            $this->check($record['attempts'] === 1 && $record['expires'] > time(), 'first failure creates a window');
            $record['expires'] = time() + 10;
            $this->driver->set($key, $record, 30);
            $throttle->penalize('alice', '192.0.2.1');
            $next = $this->executor->read($key)->getValue();
            $this->check($next['attempts'] === 2 && $next['expires'] === $record['expires'], 'live failure increments without extending its window');
            $this->driver->set($key, ['attempts' => 1000000, 'expires' => time() + 10], 30);
            $throttle->penalize('alice', '192.0.2.1');
            $this->check($this->executor->read($key)->getValue()['attempts'] === 1000000, 'attempt cap is retained');
            $this->driver->set($key, ['attempts' => 7, 'expires' => time() - 1], 30);
            $throttle->penalize('alice', '192.0.2.1');
            $record = $this->executor->read($key)->getValue();
            $this->check($record['attempts'] === 1 && $record['expires'] > time(), 'expired window resets');
        } elseif ($method === 'blocked') {
            $this->check(!$throttle->blocked('alice', '192.0.2.1'), 'fresh identity allowed');
            $throttle->penalize('alice', '192.0.2.1');
            $this->check(!$throttle->blocked('alice', '192.0.2.1'), 'below threshold allowed');
            $throttle->penalize('alice', '192.0.2.1');
            $this->check($throttle->blocked('alice', '192.0.2.1'), 'threshold blocks');
            $this->check(!$throttle->blocked('bob', '192.0.2.1') && !$throttle->blocked('alice', '192.0.2.2'), 'subject and address isolate windows');
            $this->check(!(new NoSqlThrottle($this->executor, 'mfa', 2, 30))->blocked('alice', '192.0.2.1'), 'scope isolates windows');
            $this->driver->set($key, ['attempts' => 2, 'expires' => time()], 30);
            $this->check(!$throttle->blocked('alice', '192.0.2.1'), 'expired window unblocks at boundary');
        } elseif ($method === 'validation') {
            $this->rejects(fn() => new NoSqlThrottle($this->executor, 'form', 0, 30), \InvalidArgumentException::class, 'invalid threshold rejected');
            $this->rejects(fn() => new NoSqlThrottle($this->executor, 'form', 2, 0), \InvalidArgumentException::class, 'invalid window lifetime rejected');
            $this->driver->set($key, ['attempts' => 'bad', 'expires' => time() + 30], 30);
            $this->rejects(fn() => $throttle->blocked('alice', '192.0.2.1'), ValidationException::class, 'malformed persisted counter rejected');
        }
    }

    private function migration(string $method): void
    {
        $store = new MigrationStore($this->executor);
        $key = $this->executor->getKeyspace()->getKey('migrations', 'records');
        if ($method === 'exists') {
            $this->check(!$store->exists(), 'ledger initially absent');
            $store->create();
            $this->check($store->exists(), 'created empty ledger exists');
        } elseif ($method === 'create') {
            $store->create();
            $store->save('FirstMigration', Status::PASSED);
            $before = $this->executor->read($key)->getVersion();
            $store->create();
            $this->check($store->read() === ['FirstMigration' => Status::PASSED] && $this->executor->read($key)->getVersion() === $before, 'repeated create never replaces existing statuses');
        } elseif ($method === 'save' || $method === 'read') {
            foreach (Status::cases() as $status) $store->save('Migration' . $status->name, $status);
            $expected = ['MigrationPENDING' => Status::PENDING, 'MigrationFAILED' => Status::FAILED, 'MigrationPASSED' => Status::PASSED];
            $this->check($store->read() === $expected, 'status codes round-trip without dropping other migrations');
            $store->save('MigrationPENDING', Status::PASSED);
            $this->check($store->read()['MigrationPENDING'] === Status::PASSED && count($store->read()) === 3, 'changing one status retains other entries');
        } elseif ($method === 'remove') {
            $store->save('FirstMigration', Status::PENDING);
            $store->save('SecondMigration', Status::PASSED);
            $store->remove('FirstMigration');
            $before = $this->executor->read($key)->getVersion();
            $store->remove('FirstMigration');
            $this->check($store->read() === ['SecondMigration' => Status::PASSED], 'only selected migration removed');
            $this->check($this->executor->read($key)->getVersion() === $before, 'removing an absent entry is idempotent');
        } elseif ($method === 'validation') {
            foreach ([['records' => 'bad'], ['records' => ['Migration' => '9']], ['records' => ['Migration' => 1]]] as $record) {
                $this->driver->set($key, $record);
                $this->rejects(fn() => $store->read(), \UnexpectedValueException::class, 'invalid ledger entry rejected');
            }
        }
    }

    private function representation(): void
    {
        $store = new RepresentationStore($this->executor, 30);
        $time = $store->getTime('page');
        $this->check($time > 0 && $store->getTime('page') === $time, 'first observation time is retained');
        $key = $this->executor->getKeyspace()->getKey('representation', 'page');
        $this->driver->set($key, ['time' => $time - 100, 'expires' => time() + 30], 30);
        $this->check($store->getTime('page') === $time - 100, 'live stored timestamp is not replaced by current time');
        $this->driver->set($key, ['time' => $time - 100, 'expires' => time() - 1], 30);
        $this->check($store->getTime('page') >= $time, 'expired representation is reinitialized');
        $this->rejects(fn() => new RepresentationStore($this->executor, 0), \InvalidArgumentException::class, 'nonpositive retention rejected');
        $this->driver->set($key, ['time' => [], 'expires' => time() + 30], 30);
        $this->rejects(fn() => $store->getTime('page'), ValidationException::class, 'malformed stored observation rejected');
    }

    private function session(string $method): void
    {
        $store = new SessionStore($this->executor, 30, 0);
        if ($method === 'read') {
            $this->check($store->read('one') === '', 'new session has empty bytes');
            $store->close();
            $store->write('one', "payload\0\xff", 60);
            $store->close();
            $this->check($store->read('one') === "payload\0\xff", 'session payload round-trips exactly');
        } elseif ($method === 'write') {
            $store->write('one', "first\0\xff", 60);
            $this->check($store->read('one') === "first\0\xff", 'write persists exact session bytes');
            $store->write('one', 'replacement', 60);
            $this->check($store->read('one') === 'replacement', 'same owner can replace payload');
            $other = new SessionStore($this->executor, 30, 0);
            $this->rejects(fn() => $other->read('one'), \RuntimeException::class, 'another session lifecycle cannot enter owned lease');
        } elseif ($method === 'destroy') {
            $store->write('one', 'payload', 60);
            $store->destroy('one');
            $this->check($store->read('one') === '', 'destroy clears payload while retaining ownership');
        } elseif ($method === 'renew') {
            $store->write('one', 'payload', 60);
            $key = $this->executor->getKeyspace()->getKey('session', 'one');
            $before = $this->executor->read($key);
            $store->renew();
            $after = $this->executor->read($key);
            $this->check($after->getVersion() !== $before->getVersion() && $after->getValue()['payload'] === 'payload' && $after->getValue()['payload_expires'] === $before->getValue()['payload_expires'], 'renewal changes lease without extending payload lifetime');
        } elseif ($method === 'close') {
            $store->write('one', 'payload', 60);
            $store->close();
            $store->close();
            $other = new SessionStore($this->executor, 30, 0);
            $this->check($other->read('one') === 'payload', 'close releases ownership without deleting live payload');
            $other->close();
        } elseif ($method === 'validation') {
            $this->rejects(fn() => $store->write('one', 'payload', 0), \InvalidArgumentException::class, 'nonpositive payload lifetime rejected');
            $this->rejects(fn() => new SessionStore($this->executor, 0, 0), \InvalidArgumentException::class, 'invalid session lease rejected');
        }
        $store->close();
    }
}

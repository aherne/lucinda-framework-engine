<?php

namespace Test\Lucinda\Framework\Support;

use Lucinda\Framework\NoSQL\AtomicDriver;
use Lucinda\Framework\NoSQL\AtomicDriver\APCuAtomicDriver;
use Lucinda\Framework\NoSQL\AtomicDriver\MemcachedAtomicDriver;
use Lucinda\Framework\NoSQL\AtomicDriver\RedisAtomicDriver;
use Lucinda\Framework\NoSqlDriverProvider;
use Lucinda\NoSQL\KeyNotFoundException;
use Lucinda\NoSQL\OperationFailedException;
use Test\Lucinda\Framework\NoSQL\AtomicDriverContract;

/**
 * Owns a real backend used only by one isolated test worker
 *
 * Network backends bind a fresh loopback port without persistence. APCu lives
 * only in this CLI worker. No application endpoint or stored data is used.
 */
final class AtomicBackendFixture
{
    /** Backend selected by the owning test session */
    private string $backend;

    /** Provider retained while its adapters use the original connection */
    private NoSqlDriverProvider $provider;

    /** Atomic adapter exercised by this fixture */
    private AtomicDriver $driver;

    /** @var resource|null Foreground server process owned by this fixture */
    private mixed $server = null;

    /** Unique logical key prefix inside the private backend */
    private string $prefix;

    /**
     * Starts a private backend and maps its driver through the real provider
     *
     * @param string $backend redis, valkey, memcached or apcu
     * @throws \RuntimeException If the selected backend cannot be started
     */
    public function __construct(string $backend)
    {
        $this->backend = $backend;
        $this->prefix = 'framework-test:' . bin2hex(random_bytes(12)) . ':';
        try {
            $xml = new \SimpleXMLElement('<xml><nosql><server/></nosql></xml>');
            $xml->nosql->server->addAttribute('driver', $backend);
            if ($backend !== 'apcu') {
                $port = $this->startServer();
                $xml->nosql->server->addAttribute('host', '127.0.0.1:' . $port);
            }
            $this->provider = new NoSqlDriverProvider($xml);
            $this->driver = $this->provider->getAtomicDriver();
            $this->requirePrivateServer();
        } catch (\Throwable $error) {
            $this->stopServer();
            throw $error;
        }
    }

    /**
     * Starts a foreground server on a newly allocated loopback port
     *
     * @return int Port used only by this fixture
     * @throws \RuntimeException If binding or server startup fails
     */
    private function startServer(): int
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0', $code, $error);
        if ($socket === false) {
            throw new \RuntimeException('Cannot reserve test port: ' . $error);
        }
        $address = stream_socket_get_name($socket, false);
        $port = (int) substr($address, strrpos($address, ':') + 1);
        fclose($socket);
        if ($this->backend === 'redis' || $this->backend === 'valkey') {
            $command = [
                'redis-server', '--bind', '127.0.0.1', '--port', (string) $port,
                '--save', '', '--appendonly', 'no',
            ];
        } elseif ($this->backend === 'memcached') {
            $command = [
                'memcached', '-l', '127.0.0.1', '-p', (string) $port, '-U', '0',
            ];
            if (function_exists('posix_geteuid') && posix_geteuid() === 0) {
                $command[] = '-u';
                $command[] = 'nobody';
            }
        } else {
            throw new \RuntimeException('Unsupported test backend');
        }
        $this->server = proc_open($command, [
            0 => ['file', '/dev/null', 'r'],
            1 => ['file', '/dev/null', 'w'],
            2 => ['file', '/dev/null', 'w'],
        ], $pipes);
        if (!is_resource($this->server)) {
            throw new \RuntimeException('Cannot start isolated cache server');
        }
        $deadline = microtime(true) + 3;
        do {
            $connection = @stream_socket_client(
                'tcp://127.0.0.1:' . $port,
                $code,
                $error,
                0.05
            );
            if ($connection !== false) {
                fclose($connection);
                return $port;
            }
            if (!proc_get_status($this->server)['running']) {
                throw new \RuntimeException('Install the cache server binary');
            }
            usleep(10000);
        } while (microtime(true) < $deadline);
        throw new \RuntimeException('Isolated cache server startup timed out');
    }

    /**
     * Creates another adapter owner sharing this fixture's native backend
     *
     * @return AtomicDriver Independent snapshot owner
     */
    private function other(): AtomicDriver
    {
        $original = $this->provider->getDriver();
        if ($this->backend === 'redis' || $this->backend === 'valkey') {
            return new RedisAtomicDriver($original);
        }
        if ($this->backend === 'memcached') {
            return new MemcachedAtomicDriver($original);
        }
        return new APCuAtomicDriver($original);
    }

    /**
     * Verifies the endpoint is the process created by this fixture
     *
     * @throws \RuntimeException If the server died or another process owns it
     */
    private function requirePrivateServer(): void
    {
        if ($this->backend === 'apcu') {
            return;
        }
        $status = proc_get_status($this->server);
        if (!$status['running']) {
            throw new \RuntimeException('Private test server has stopped');
        }
        $client = $this->provider->getDriver()->getDriver();
        if ($this->backend === 'redis' || $this->backend === 'valkey') {
            $info = $client->info('server');
            $pid = $info['process_id'] ?? null;
        } else {
            $statistics = $client->getStats();
            $info = reset($statistics);
            $pid = $info['pid'] ?? null;
        }
        if ((int) $pid !== $status['pid']) {
            throw new \RuntimeException('Refusing an unrelated cache server');
        }
    }

    /**
     * Exercises one public operation using the actual selected vendor adapter
     *
     * @param string $operation Public method or shared contract case
     * @return list<array{bool, string}> Observable assertion results
     */
    public function run(string $operation): array
    {
        $this->requirePrivateServer();
        if (str_starts_with($operation, 'persistence:')) {
            return (new PersistenceNoSqlFixture($this->driver))->run(substr($operation, strlen('persistence:')));
        }
        $key = $this->prefix . $operation;
        $driver = $this->driver;
        switch ($operation) {
            case 'getVersioned':
                $missing = $driver->getVersioned($key);
                $driver->set($key, null);
                $value = $driver->getVersioned($key);
                return [
                    [!$missing->exists(), 'explicit absence'],
                    [$value->exists(), 'stored null exists'],
                    [$value->getValue() === null, 'null preserved'],
                ];
            case 'compareAndSwap':
                $first = $driver->getVersioned($key);
                $second = $driver->getVersioned($key);
                return [
                    [$driver->compareAndSwap($first, 'winner', 0),
                        'atomic creation'],
                    [!$driver->compareAndSwap($second, 'loser', 0),
                        'stale creation rejected'],
                    [$driver->get($key) === 'winner', 'winner preserved'],
                ];
            case 'compareAndDelete':
                $driver->set($key, 'before');
                $old = $driver->getVersioned($key);
                $driver->set($key, 'after');
                $current = $driver->getVersioned($key);
                return [
                    [!$driver->compareAndDelete($old), 'stale delete rejected'],
                    [$driver->compareAndDelete($current), 'current delete'],
                    [!$driver->contains($key), 'deletion observable'],
                ];
            case 'set':
                $driver->set($key, "binary\0\xff");
                $old = $driver->getVersioned($key);
                $driver->set($key, "binary\0\xff");
                return [
                    [$driver->get($key) === "binary\0\xff", 'binary preserved'],
                    [!$driver->compareAndSwap($old, 'stale', 0),
                        'identical write invalidates version'],
                ];
            case 'contains':
                $missing = !$driver->contains($key);
                $driver->set($key, false);
                return [
                    [$missing, 'missing key'],
                    [$driver->contains($key), 'stored false exists'],
                ];
            case 'get':
                $missing = false;
                try {
                    $driver->get($key);
                } catch (KeyNotFoundException) {
                    $missing = true;
                }
                $driver->set($key, ['value' => false]);
                return [
                    [$missing, 'missing key throws'],
                    [$driver->get($key) === ['value' => false], 'typed array'],
                ];
            case 'increment':
                $driver->set($key, 1, 60);
                $old = $driver->getVersioned($key);
                $value = $driver->increment($key, 2);
                return [
                    [$value === 3, 'integer increment'],
                    [$driver->getVersioned($key)->getExpiresAt()
                        === $old->getExpiresAt(), 'deadline preserved'],
                    [!$driver->compareAndSwap($old, 100, 0), 'version changed'],
                ];
            case 'decrement':
                $driver->set($key, 1);
                $value = $driver->decrement($key, 3);
                $driver->set($key, PHP_INT_MIN);
                $overflow = false;
                try {
                    $driver->decrement($key);
                } catch (OperationFailedException) {
                    $overflow = true;
                }
                return [
                    [$value === -2, 'signed decrement'],
                    [$overflow, 'integer overflow rejected'],
                ];
            case 'delete':
                $driver->set($key, 'value');
                $old = $driver->getVersioned($key);
                $driver->delete($key);
                return [
                    [!$driver->contains($key), 'ordinary deletion'],
                    [!$driver->compareAndSwap($old, 'stale', 0),
                        'deleted revision invalidated'],
                ];
            case 'flush':
                $driver->set($key, 'value');
                $driver->flush();
                return [[!$driver->contains($key), 'private backend flushed']];
            case 'provider':
                return [
                    [$driver === $this->provider->getAtomicDriver(),
                        'atomic adapter is cached'],
                    [$this->provider->getDriver() !== $driver,
                        'original library driver remains separate'],
                ];
            case 'contract':
                $contract = new AtomicDriverContract(
                    $driver,
                    $this->other(),
                    $this->prefix . 'shared:'
                );
                $count = $contract->run();
                return [[true, 'shared atomic contract: ' . $count . ' checks']];
        }
        throw new \RuntimeException('Unknown atomic test operation');
    }

    /**
     * Terminates only the foreground server created by this fixture
     */
    private function stopServer(): void
    {
        if (is_resource($this->server)) {
            if (proc_get_status($this->server)['running']) {
                proc_terminate($this->server);
            }
            proc_close($this->server);
            $this->server = null;
        }
    }

    /**
     * Releases the private server when the worker runtime leaves scope
     */
    public function __destruct()
    {
        $this->stopServer();
    }
}

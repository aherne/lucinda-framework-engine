<?php

namespace Lucinda\Framework\NoSQL;

use Lucinda\Framework\NoSQL\VersionedValue;
use Lucinda\Framework\Persistence\Builders\Row;
use Lucinda\Framework\Persistence\Queries\ParametersValidator;
use Lucinda\Framework\Persistence\Locking\NoSqlLock;
use Lucinda\Framework\Persistence\NoSqlExecutor;

/**
 * Persists session bytes under a lease sharing the same atomic record
 *
 * NoSqlLock owns acquisition, renewal and release. Payload replacements compare
 * the record containing that ownership, rejecting writers from an old lease.
 * Payload expiration is independent of the lease deadline.
 *
 * @internal
 */
final class SessionStore
{
    /** Lease protecting this lifecycle and its session payload record */
    private NoSqlLock $lock;

    /**
     * Creates a session-domain lease on the injected atomic connection
     *
     * @param NoSqlExecutor $executor Shared connection and namespace
     * @param int $leaseLifetime Positive lease lifetime in seconds
     * @param int $waitTimeout Nonnegative acquisition timeout in seconds
     * @throws \InvalidArgumentException If a lifetime is invalid
     */
    public function __construct(
        NoSqlExecutor $executor,
        int $leaseLifetime = 60,
        int $waitTimeout = 10
    ) {
        $this->lock = new NoSqlLock(
            $executor,
            'session',
            $leaseLifetime,
            $waitTimeout
        );
    }

    /**
     * Validates session fields or initializes a newly acquired lease record
     *
     * @param VersionedValue $snapshot Record observed under the current lease
     * @return array{token: string, lease_expires: int, payload: string,
     *     payload_expires: int} Validated session and ownership fields
     * @throws \UnexpectedValueException If stored fields are malformed
     * @throws \Lucinda\Framework\Persistence\Queries\ValidationException If a session column has an incompatible type
     */
    private function getRecord(VersionedValue $snapshot): array
    {
        $values = $snapshot->getValue();
        if (
            !array_key_exists('payload', $values)
            && !array_key_exists('payload_expires', $values)
        ) {
            $values['payload'] = '';
            $values['payload_expires'] = 0;
        }
        $row = new Row($values, new ParametersValidator());
        return [
            'token' => $row->getString('token')
                ?? throw new \UnexpectedValueException('Null session token'),
            'lease_expires' => $row->getInteger('lease_expires')
                ?? throw new \UnexpectedValueException('Null session lease expiry'),
            'payload' => $row->getString('payload')
                ?? throw new \UnexpectedValueException('Null session payload'),
            'payload_expires' => $row->getInteger('payload_expires')
                ?? throw new \UnexpectedValueException('Null session payload expiry'),
        ];
    }

    /**
     * Keeps a record until both its payload and lease have expired
     *
     * @param array<string, mixed> $record Validated session fields
     * @return int Positive retention lifetime in seconds
     */
    private function getLifetime(array $record): int
    {
        return max(
            1,
            max($record['lease_expires'], $record['payload_expires']) - time()
        );
    }

    /**
     * Reads session bytes while retaining the lease until close
     *
     * @param string $id PHP session identifier
     * @return string Original binary payload or an empty string
     * @throws \Throwable If acquisition, ownership or stored fields fail
     */
    public function read(string $id): string
    {
        $this->lock->acquire($id);
        $record = $this->getRecord($this->lock->read());
        if ($record['payload_expires'] <= time()) {
            return '';
        }
        return $record['payload'];
    }

    /**
     * Atomically changes payload fields while retaining valid lease ownership
     *
     * @param string $id PHP session identifier
     * @param string|null $payload New binary bytes, null for deletion
     * @param int $lifetime Payload lifetime in seconds
     * @throws \RuntimeException If ownership is lost or contention persists
     * @throws \Throwable If validation or storage fails
     *
     * @return void
     */
    private function update(
        string $id,
        ?string $payload,
        int $lifetime
    ): void {
        $this->lock->acquire($id);
        for ($attempt = 0; $attempt < 1000; $attempt++) {
            $snapshot = $this->lock->read();
            $record = $this->getRecord($snapshot);
            $record['payload'] = $payload ?? '';
            $record['payload_expires'] = $payload === null
                ? 0 : time() + $lifetime;
            if (
                $this->lock->replace(
                    $snapshot,
                    $record,
                    $this->getLifetime($record)
                )
            ) {
                return;
            }
        }
        throw new \RuntimeException('Session contention exceeded limit');
    }

    /**
     * Persists binary session bytes only while the lease remains owned
     *
     * @param string $id PHP session identifier
     * @param string $payload Serialized session bytes
     * @param int $lifetime Positive payload lifetime in seconds
     * @throws \InvalidArgumentException If the lifetime is not positive
     * @throws \Throwable If ownership is lost or persistence fails
     *
     * @return void
     */
    public function write(string $id, string $payload, int $lifetime): void
    {
        if ($lifetime < 1) {
            throw new \InvalidArgumentException('Invalid session lifetime');
        }
        $this->update($id, $payload, $lifetime);
    }

    /**
     * Clears payload bytes without releasing the current lease
     *
     * @param string $id PHP session identifier
     * @throws \Throwable If ownership is lost or deletion fails
     *
     * @return void
     */
    public function destroy(string $id): void
    {
        $this->update($id, null, 0);
    }

    /**
     * Renews the active lease without changing the payload deadline
     *
     * @throws \Throwable If no live lease is owned or storage fails
     *
     * @return void
     */
    public function renew(): void
    {
        $this->lock->renew();
    }

    /**
     * Releases this lifecycle without changing another owner's record
     *
     * @throws \Throwable If storage or conditional release fails
     *
     * @return void
     */
    public function close(): void
    {
        $this->lock->release();
    }
}

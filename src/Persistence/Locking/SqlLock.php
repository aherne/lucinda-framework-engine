<?php

namespace Lucinda\Framework\Persistence\Locking;

use Lucinda\Framework\Persistence\Queries\Locks\AcquireLock;
use Lucinda\Framework\Persistence\Queries\Locks\ReleaseLock;
use Lucinda\Framework\Persistence\SqlExecutor;
use Lucinda\SQL\DataType;

/**
 * Owns one advisory lock through the executor's retained connection
 *
 * The lock namespace belongs to the caller. Ownership is tracked separately
 * from the storage operation protected by this lock.
 *
 * @internal
 */
final class SqlLock implements Lock
{
    /** Executor retaining the connection that owns the advisory lock */
    private SqlExecutor $sql;

    /** Namespace distinguishing this caller's protected identities */
    private string $namespace;

    /** Maximum acquisition wait in seconds, zero for a nonblocking attempt */
    private int $timeout;

    /** Advisory lock name currently owned through this instance */
    private ?string $name = null;

    /**
     * Captures the connection and namespace used to establish ownership
     *
     * @param SqlExecutor $sql Executor shared with the protected operation
     * @param string $namespace Namespace for this caller's lock identities
     * @param int $timeout Maximum acquisition wait in seconds
     * @throws \InvalidArgumentException If namespace or timeout is invalid
     */
    public function __construct(
        SqlExecutor $sql,
        string $namespace,
        int $timeout = 10
    ) {
        if ($namespace === '' || $timeout < 0) {
            throw new \InvalidArgumentException('Invalid SQL lock policy');
        }
        $this->sql = $sql;
        $this->namespace = $namespace;
        $this->timeout = $timeout;
    }

    /**
     * Acquires the requested identity without reentering an already owned lock
     *
     * @param string $identity Identity protected by the coordinating caller
     * @throws \RuntimeException If the acquisition times out
     * @throws \Throwable If immediate persistence or database access fails
     */
    public function acquire(string $identity): void
    {
        $name = hash(
            'sha256',
            json_encode([$this->namespace, $identity], JSON_THROW_ON_ERROR)
        );
        if ($name === $this->name) {
            return;
        }
        $this->release();
        $this->sql->requireAutocommit();
        $deadline = hrtime(true) + $this->timeout * 1000000000;
        do {
            $query = new AcquireLock($this->sql->getVendor(), $name, 0);
            $value = $this->sql->read($query)->toValue(DataType::INTEGER);
            if ($value !== null && $value === 1) {
                $this->name = $name;
                return;
            }
            $remaining = $deadline - hrtime(true);
            if ($remaining <= 0) {
                break;
            }
            usleep((int) min(10000, ceil($remaining / 1000)));
        } while (true);
        throw new \RuntimeException('SQL lock acquisition timed out');
    }

    /**
     * Releases the advisory lock owned by this instance and connection
     *
     * @throws \RuntimeException If ownership cannot be confirmed on release
     * @throws \Throwable If the release operation fails
     */
    public function release(): void
    {
        if ($this->name === null) {
            return;
        }
        $query = new ReleaseLock($this->sql->getVendor(), $this->name);
        $value = $this->sql->read($query)->toValue(DataType::INTEGER);
        if ($value === null || $value !== 1) {
            throw new \RuntimeException('SQL lock ownership was lost');
        }
        $this->name = null;
    }

    /**
     * Attempts release before the owner leaves scope
     */
    public function __destruct()
    {
        try {
            $this->release();
        } catch (\Throwable) {
            // Connection termination remains the advisory-lock recovery path.
        }
    }
}

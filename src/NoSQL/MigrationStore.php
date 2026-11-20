<?php

namespace Lucinda\Framework\NoSQL;

use Lucinda\Framework\NoSQL\VersionedValue;
use Lucinda\Migration\Status;
use Lucinda\Framework\Persistence\NoSqlExecutor;

/**
 * Owns one migration ledger independently of its NoSQL backend
 *
 * Conditional replacement preserves concurrent updates to different records.
 * Ledger initialization and repeated status writes are idempotent.
 *
 * @internal
 */
final class MigrationStore
{
    /** Executor used for atomic ledger replacement */
    private NoSqlExecutor $executor;

    /** Namespaced identity of the application's ledger */
    private string $key;

    /**
     * Captures the executor and resolves this application's ledger key
     *
     * @param NoSqlExecutor $executor Connection and namespace for this ledger
     */
    public function __construct(NoSqlExecutor $executor)
    {
        $this->executor = $executor;
        $this->key = $executor->getKeyspace()->getKey('migrations', 'records');
    }

    /**
     * Validates the migration entries in one observed ledger
     *
     * @param VersionedValue $snapshot Current ledger observation
     * @return array<string, string> Stored status codes by migration class
     * @throws \UnexpectedValueException If the ledger has malformed fields
     */
    private function getRecords(VersionedValue $snapshot): array
    {
        $values = ($snapshot->exists() ? $snapshot->getValue() : []);
        if ($values === []) {
            return [];
        }
        $records = $values['records'] ?? null;
        if (!is_array($records)) {
            throw new \UnexpectedValueException('Invalid migration ledger');
        }
        foreach ($records as $name => $value) {
            if (
                !is_string($name) || !is_string($value)
                || !in_array($value, ['1', '2', '3'], true)
            ) {
                throw new \UnexpectedValueException('Invalid ledger field');
            }
        }
        return $records;
    }

    /**
     * Checks whether this application's ledger has been initialized
     *
     * @return bool True when a ledger record exists
     * @throws \Throwable If storage or validation fails
     */
    public function exists(): bool
    {
        $snapshot = $this->executor->read($this->key);
        $this->getRecords($snapshot);
        return ($snapshot->exists() ? $snapshot->getValue() : []) !== [];
    }

    /**
     * Initializes an absent ledger without replacing existing statuses
     *
     * @throws \RuntimeException If excessive contention prevents initialization
     * @throws \Throwable If storage or validation fails
     *
     * @return void
     */
    public function create(): void
    {
        for ($attempt = 0; $attempt < 1000; $attempt++) {
            $snapshot = $this->executor->read($this->key);
            $this->getRecords($snapshot);
            if (($snapshot->exists() ? $snapshot->getValue() : []) !== []) {
                return;
            }
            if ($this->executor->replace($snapshot, ['records' => []])) {
                return;
            }
        }
        throw new \RuntimeException('Ledger contention exceeded limit');
    }

    /**
     * Changes one status while preserving the latest concurrent entries
     *
     * @param string $className Migration class to change
     * @param Status|null $status New status, null to remove the entry
     * @throws \RuntimeException If excessive contention prevents the change
     * @throws \Throwable If storage or validation fails
     *
     * @return void
     */
    private function update(string $className, ?Status $status): void
    {
        for ($attempt = 0; $attempt < 1000; $attempt++) {
            $snapshot = $this->executor->read($this->key);
            $records = $this->getRecords($snapshot);
            if ($status === null) {
                if (!isset($records[$className])) {
                    return;
                }
                unset($records[$className]);
            } else {
                $records[$className] = (string) $status->value;
            }
            if ($this->executor->replace($snapshot, ['records' => $records])) {
                return;
            }
        }
        throw new \RuntimeException('Ledger contention exceeded limit');
    }

    /**
     * Persists one migration status independently of concurrent changes
     *
     * @param string $className Fully qualified migration class name
     * @param Status $status Backed migration execution status
     * @throws \Throwable If persistence fails
     *
     * @return void
     */
    public function save(string $className, Status $status): void
    {
        $this->update($className, $status);
    }

    /**
     * Reads and converts all recorded statuses to migration API values
     *
     * @return array<string, Status> Statuses indexed by migration class
     * @throws \Throwable If storage or record validation fails
     */
    public function read(): array
    {
        $snapshot = $this->executor->read($this->key);
        $records = $this->getRecords($snapshot);
        $statuses = [];
        foreach ($records as $name => $value) {
            switch ($value) {
                case '1':
                    $statuses[$name] = Status::PENDING;
                    break;
                case '2':
                    $statuses[$name] = Status::FAILED;
                    break;
                case '3':
                    $statuses[$name] = Status::PASSED;
                    break;
            }
        }
        return $statuses;
    }

    /**
     * Removes one migration without replacing concurrent ledger entries
     *
     * @param string $className Fully qualified migration class name
     * @throws \Throwable If persistence fails
     *
     * @return void
     */
    public function remove(string $className): void
    {
        $this->update($className, null);
    }
}

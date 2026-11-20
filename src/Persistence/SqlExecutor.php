<?php

namespace Lucinda\Framework\Persistence;

use Lucinda\Framework\Persistence\Queries\ConnectionSettings\ReadAutocommit;
use Lucinda\Framework\Persistence\Queries\Query;
use Lucinda\Framework\Persistence\Results\Write;
use Lucinda\Framework\Persistence\Results\Read;
use Lucinda\SQL\Connection;
use Lucinda\SQL\DataType;
use Lucinda\SQL\StatementResults;
use Lucinda\SQL\Transaction;

/**
 * Executes generated SQL operations through one retained connection
 *
 * The caller injects the connection and coordinates transactions explicitly.
 * Queries carry validated values and SQL binding types. Read and write wrappers
 * consume results without exposing the native PDO connection.
 *
 * @internal
 */
final class SqlExecutor
{
    /** Connection used for every operation in this executor */
    private Connection $connection;

    /** Transaction explicitly started and owned by this executor */
    private ?Transaction $transaction = null;

    /**
     * Captures the connection retained for every operation
     *
     * @param Connection $connection Shared connection for this operation
     */
    public function __construct(
        Connection $connection
    ) {
        $this->connection = $connection;
    }

    /**
     * Prepares and executes one generated statement with typed bindings
     *
     * @param Query $query Generated operation and bound runtime values
     * @return StatementResults Driver result consumed within this executor
     * @throws \Throwable If preparation, binding or execution fails
     */
    private function execute(Query $query): StatementResults
    {
        $statement = $this->connection->preparedStatement();
        $statement->prepare($query->getSql());
        foreach ($query->getParameters() as $name => $value) {
            $statement->bind($name, $value["value"], $value["type"]);
        }
        return $statement->execute();
    }

    /**
     * Executes a selection and exposes its cursor through the typed read API.
     *
     * @param Lucinda\Framework\Persistence\Queries\Query $query Generated operation carrying explicitly typed runtime bindings.
     * @return Lucinda\Framework\Persistence\Results\Read
     * @throws \LogicException If the statement category is inappropriate.
     */
    public function read(Query $query): Read
    {
        if (!$query->isSelection()) {
            throw new \LogicException('Expected a selection operation');
        }
        return new Read($this->execute($query));
    }

    /**
     * Executes a write and exposes its affected count and generated identifier.
     *
     * @param Lucinda\Framework\Persistence\Queries\Query $query Generated operation carrying explicitly typed runtime bindings.
     * @return Lucinda\Framework\Persistence\Results\Write
     * @throws \LogicException If the statement category is inappropriate.
     */
    public function write(Query $query): Write
    {
        if ($query->isSelection()) {
            throw new \LogicException('Expected a database write operation');
        }
        return new Write($this->execute($query));
    }

    /**
     * Starts a transaction explicitly owned by the coordinating caller
     *
     * @throws \LogicException If another transaction is already active
     * @throws \Throwable If the database cannot start the transaction
     */
    public function begin(): void
    {
        $transaction = $this->connection->transaction();
        if ($transaction->isActive()) {
            throw new \LogicException('Nested transactions are unsupported');
        }
        $transaction->begin();
        $this->transaction = $transaction;
    }

    /**
     * Commits the transaction started through this executor
     *
     * @throws \LogicException If this executor owns no active transaction
     * @throws \Throwable If committing fails
     */
    public function commit(): void
    {
        if ($this->transaction === null) {
            throw new \LogicException('No owned transaction to commit');
        }
        $this->transaction->commit();
        $this->transaction = null;
    }

    /**
     * Rolls back an owned transaction after a failed coordinated operation
     *
     * @throws \Throwable If the database cannot roll back
     */
    public function rollback(): void
    {
        if ($this->transaction !== null) {
            if ($this->transaction->isActive()) {
                $this->transaction->rollback();
            }
            $this->transaction = null;
        }
    }

    /**
     * Requires immediate persistence for single-use security operations
     *
     * @throws \LogicException If a transaction is active or autocommit is off
     * @throws \Throwable If the setting cannot be read
     */
    public function requireAutocommit(): void
    {
        if ($this->connection->transaction()->isActive()) {
            throw new \LogicException('Operation requires no transaction');
        }
        if ($this->getVendor() !== SqlVendor::MySQL) {
            return;
        }
        $value = $this->read(new ReadAutocommit())->toValue(DataType::BOOLEAN); // MySQL case
        if (empty($value)) {
            throw new \LogicException('MySQL autocommit must be enabled');
        }
    }

    /** Gets the dialect of the retained connection. */
    public function getVendor(): SqlVendor
    {
        return match ($this->connection->getDriverName()) {
            'mysql' => SqlVendor::MySQL,
            'pgsql' => SqlVendor::PostgreSQL,
            'sqlite' => SqlVendor::SQLite,
            default => throw new \LogicException('Unsupported SQL driver'),
        };
    }
}

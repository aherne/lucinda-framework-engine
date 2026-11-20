<?php

namespace Lucinda\Framework\Persistence\Results;

use Lucinda\SQL\StatementResults;

/**
 * Describes the outcome of one completed database write
 *
 * Affected-row counts support atomic acceptance checks. The inserted identifier
 * is meaningful only for writes that create an auto-incremented record.
 *
 * @internal
 */
final class Write
{
    private StatementResults $statementResults;

    /**
     * Captures the write metadata supplied by the database
     *
     * @param StatementResults $statementResults;
     */
    public function __construct(
        StatementResults $statementResults
    ) {
        $this->statementResults = $statementResults;
    }

    /**
     * Gets the affected-row count captured when this operation completed
     *
     * @return int Number of records changed by this operation
     */
    public function getAffectedRows(): int
    {
        return $this->statementResults->getAffectedRows();
    }

    /**
     * Gets the generated identifier captured when this operation completed
     *
     * @return int Insert identifier, meaningful for auto-incremented records
     */
    public function getInsertId(): int
    {
        return $this->statementResults->getInsertId();
    }
}

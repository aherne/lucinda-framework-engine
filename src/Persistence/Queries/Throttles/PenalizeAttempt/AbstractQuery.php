<?php

namespace Lucinda\Framework\Persistence\Queries\Throttles\PenalizeAttempt;

use Lucinda\Framework\Persistence\Queries\Query;
use Lucinda\Framework\Persistence\Queries\Parameters;
use Lucinda\SQL\DataType;
use Lucinda\Query\Insert;

/** Owns fixed-window values and updates common to all SQL vendors. @internal */
abstract class AbstractQuery extends Query
{
    /**
     * Captures the operation inputs and initializes its typed bindings.
     *
     * @param string $key Selected column or named parameter identifier.
     * @param int $now Current Unix timestamp used to evaluate expiration.
     * @param int $period Fixed failure-window lifetime in seconds.
     */
    public function __construct(string $key, int $now, int $period)
    {
        $this->statement = $this->createInsert();
        $this->setColumns();
        $this->setValues();
        // MySQL evaluates assignments in order, so update attempts before expiry.
        $this->setConflictUpdate([
            'attempts' => $this->getExpressionAttempts(),
            'expires' => $this->getExpressionExpires()
        ]);
        $this->setParameters($key, $now, $period);
    }

    /**
     * Defines the bucket, attempt count and expiration insertion columns.
     *
     * @return void
     */
    private function setColumns(): void
    {
        $this->statement->columns([
            'bucket',
            'attempts',
            'expires'
            ]);
    }

    /**
     * Defines the initial attempt count and named insertion placeholders.
     *
     * @return void
     */
    private function setValues(): void
    {
        $this->statement->values([
            ':key',
            '1',
            ':expires'
            ]);
    }

    /**
     * Records runtime values with their explicit SQL binding types.
     *
     * @param string $key Selected column or named parameter identifier.
     * @param int $now Current Unix timestamp used to evaluate expiration.
     * @param int $period Fixed failure-window lifetime in seconds.
     * @return void
     */
    private function setParameters(string $key, int $now, int $period): void
    {
        $this->parameters = new Parameters();
        $this->parameters
            ->set(':key', $key, DataType::STRING)
            ->set(':expires', $now + $period, DataType::INTEGER)
            ->set(':now', $now, DataType::INTEGER)
            ->set(':reset_now', $now, DataType::INTEGER)
            ->set(':reset_expires', $now + $period, DataType::INTEGER);
    }

    /**
     * Resets expired counters or increments live counters up to the storage cap.
     *
     * @return string
     */
    private function getExpressionAttempts(): string
    {
        return 'CASE WHEN authentication_throttles.expires <= :now THEN 1 '
            . 'WHEN authentication_throttles.attempts >= 1000000 THEN 1000000 '
            . 'ELSE authentication_throttles.attempts + 1 END';
    }

    /**
     * Resets expired deadlines without extending live failure windows.
     *
     * @return string
     */
    private function getExpressionExpires(): string
    {
        return 'CASE WHEN authentication_throttles.expires <= :reset_now '
            . 'THEN :reset_expires ELSE authentication_throttles.expires END';
    }

    /**
     * Creates the insertion builder for this SQL vendor.
     *
     * @return Lucinda\Query\Insert
     */
    abstract protected function createInsert(): Insert;

    /**
     * Applies the vendor conflict clause to the supplied assignments.
     *
     * @param array $updates Assignments applied when the bucket already exists.
     * @return void
     */
    abstract protected function setConflictUpdate(array $updates): void;
}

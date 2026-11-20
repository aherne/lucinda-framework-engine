<?php

namespace Lucinda\Framework\Persistence\Queries\Locks\AcquireLock;

use Lucinda\Framework\Persistence\Queries\Query;
use Lucinda\Query\Clause\Fields;

/** Owns the common lifecycle and rendering of a scalar advisory-lock operation. @internal */
abstract class AbstractQuery extends Query
{
    protected Fields $fields;

    /**
     * Captures the operation inputs and initializes its typed bindings.
     *
     * @param string $name Stable advisory-lock identity shared by contenders.
     * @param int $timeout Maximum MySQL wait in seconds; PostgreSQL retries are coordinated by SqlLock.
     */
    public function __construct(string $name, int $timeout = 10)
    {
        if ($timeout < 0) {
            throw new \InvalidArgumentException('Lock timeout cannot be negative');
        }
        $this->setParameters($name, $timeout);
        $this->setFields();
    }

    /**
     * Records runtime values with their explicit SQL binding types.
     *
     * @param string $name Stable advisory-lock identity shared by contenders.
     * @param int $timeout Maximum MySQL wait in seconds; PostgreSQL retries are coordinated by SqlLock.
     * @return void
     */
    abstract protected function setParameters(string $name, int $timeout): void;

    /**
     * Defines the selected expression and its value alias.
     *
     * @return void
     */
    private function setFields(): void
    {
        $this->fields = new Fields();
        $this->fields->add($this->getExpression(), 'value');
    }

    /**
     * Returns the backend expression executed for this operation.
     *
     * @return string
     */
    abstract protected function getExpression(): string;

    /**
     * Renders the operation as SQL with named placeholders.
     *
     * @return string
     */
    public function getSql(): string
    {
        return 'SELECT ' . $this->fields;
    }

    /**
     * Reports whether this operation returns selected rows.
     *
     * @return bool
     */
    public function isSelection(): bool
    {
        return true;
    }
}

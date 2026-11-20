<?php

namespace Lucinda\Framework\Persistence\Queries\Locks\ReleaseLock;

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
     */
    public function __construct(string $name)
    {
        $this->setParameters($name);
        $this->setFields();
    }

    /**
     * Records runtime values with their explicit SQL binding types.
     *
     * @param string $name Stable advisory-lock identity shared by contenders.
     * @return void
     */
    abstract protected function setParameters(string $name): void;

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

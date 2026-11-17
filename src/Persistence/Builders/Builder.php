<?php

namespace Lucinda\Framework\Persistence\Builders;

use Lucinda\Framework\Persistence\Entities\Entity;

/**
 * Owns the selected row shared with a concrete entity builder
 *
 * Subclasses convert this projection through its typed column accessors.
 * This base class neither executes queries nor interprets entity fields.
 *
 * @internal
 */
abstract class Builder
{
    /** Projection owned by this builder */
    protected Row $row;

    /**
     * Captures the database row to convert
     *
     * @param Row $row Selected record with validated column access
     */
    public function __construct(Row $row)
    {
        $this->row = $row;
    }

    /**
     * Converts the owned row into its concrete logical persistence record
     *
     * @return Entity Record type refined by each concrete builder
     * @throws \UnexpectedValueException If selected fields are malformed
     */
    abstract public function build(): Entity;
}

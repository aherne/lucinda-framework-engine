<?php

namespace Lucinda\Framework\Persistence\Builders;

use Lucinda\Framework\Persistence\Entities\Entity;
use Lucinda\Framework\Persistence\Queries\ValidationException;

/**
 * Builds a concrete persistence entity from one validated row
 *
 * Implementations read typed columns and construct their own entity type.
 * The caller supplies each row so one builder can process an entire result set.
 *
 * @internal
 */
interface Builder
{
    /**
     * Converts one row into its concrete logical persistence record
     *
     * @param Row $row Selected record with validated column access
     * @return Entity Record type refined by each concrete builder
     * @throws ValidationException If selected fields are missing or malformed
     */
    public function build(Row $row): Entity;
}

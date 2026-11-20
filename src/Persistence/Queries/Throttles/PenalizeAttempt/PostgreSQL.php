<?php

namespace Lucinda\Framework\Persistence\Queries\Throttles\PenalizeAttempt;

use Lucinda\Query\Insert;
use Lucinda\Query\Vendor\PostgreSQL\Insert as VendorInsert;

/** Supplies the PostgreSQL insertion builder. @internal */
final class PostgreSQL extends AbstractQuery
{
    /**
     * Creates the insertion builder for this SQL vendor.
     *
     * @return Lucinda\Query\Insert
     */
    protected function createInsert(): Insert
    {
        return new VendorInsert('authentication_throttles');
    }

    /**
     * Applies the vendor conflict clause to the supplied assignments.
     *
     * @param array $updates Assignments applied when the bucket already exists.
     * @return void
     */
    protected function setConflictUpdate(array $updates): void
    {
        $this->statement->onConflictDoUpdate(['bucket'], $updates);
    }
}

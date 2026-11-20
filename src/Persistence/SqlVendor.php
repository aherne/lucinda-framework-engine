<?php

namespace Lucinda\Framework\Persistence;

/**
 * Identifies the SQL dialect used by a configured connection.
 */
enum SqlVendor
{
    case MySQL;
    case PostgreSQL;
    case SQLite;
}

<?php

namespace Lucinda\Framework\Persistence\Entities;

/**
 * Marks an internal persistence record that must not reach presentation
 *
 * Concrete implementations own their logical data and remain final and
 * readonly. The marker defines no storage or presentation operations.
 *
 * @internal
 */
interface Entity
{
}

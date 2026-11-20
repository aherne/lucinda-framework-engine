<?php

namespace Test\Lucinda\Framework\Persistence\Queries;

use Lucinda\Framework\Persistence\Queries\Query;
use Lucinda\SQL\DataType;
use Lucinda\UnitTest\Result;

/** Shared assertions for routed queries without executing application data. */
abstract class QueryContract
{
    /** @return list<array{Query, array<string, array{value: mixed, type: DataType}>, bool, string}> */
    abstract protected function cases(): array;

    public function getSql(): array
    {
        $results = [];
        foreach ($this->cases() as [$query, $parameters, $selection, $fragment]) {
            $sql = $query->getSql();
            $results[] = new Result(str_contains($sql, $fragment), 'selects the expected SQL dialect');
            preg_match_all('/(?<!:):[a-z_]+/', $sql, $matches);
            $expected = array_keys($parameters);
            $actual = array_values(array_unique($matches[0]));
            sort($expected);
            sort($actual);
            $results[] = new Result($actual === $expected, 'every placeholder has exactly one named binding');
            foreach ($parameters as $parameter) {
                $value = $parameter['value'];
                if (is_string($value) && str_contains($value, "'")) {
                    $results[] = new Result(!str_contains($sql, $value), 'runtime strings are bound rather than interpolated');
                }
            }
        }
        return $results;
    }

    public function getParameters(): array
    {
        $results = [];
        foreach ($this->cases() as [$query, $parameters]) {
            $results[] = new Result($query->getParameters() === $parameters, 'retains all runtime values and explicit SQL types');
        }
        return $results;
    }

    public function isSelection(): array
    {
        $results = [];
        foreach ($this->cases() as [$query, $parameters, $selection]) {
            $results[] = new Result($query->isSelection() === $selection, 'dispatches to the correct executor operation');
        }
        return $results;
    }
}

<?php

namespace Lucinda\Framework\Persistence\Results;

use Lucinda\Framework\Persistence\Builders\Row;
use Lucinda\Framework\Persistence\Queries\ParametersValidator;
use Lucinda\SQL\DataType;
use Lucinda\SQL\StatementResults;
use UnexpectedValueException;

/**
 * Consumes selected results as typed values, maps and validated row projections.
 */
final class Read
{
    private StatementResults $statementResults;
    private ParametersValidator $validator;

    /**
     * Retains the selected result cursor and its value validator
     *
     * @param StatementResults $statementResults Executed selection to consume.
     */
    public function __construct(
        StatementResults $statementResults
    ) {
        $this->statementResults = $statementResults;
        $this->validator = new ParametersValidator();
    }

    /**
     * Validates the next scalar and returns null when no value exists.
     *
     * @param Lucinda\SQL\DataType $dataType Explicit expected SQL value type.
     * @return mixed
     */
    public function toValue(DataType $dataType): mixed
    {
        $value = $this->statementResults->toValue();
        if ($value === null) {
            return $value;
        } else {
            return $this->validator->validate($value, $dataType);
        }
    }

    /**
     * Validates the remaining first-column values while retaining order and nulls.
     *
     * @param Lucinda\SQL\DataType $dataType Explicit expected SQL value type.
     * @return list<mixed> Normalized values, including SQL NULL.
     */
    public function toColumn(DataType $dataType): array
    {
        $values = $this->statementResults->toColumn();
        $output = [];
        foreach ($values as $value) {
            $output[] = ($value!==null?$this->validator->validate($value, $dataType):null);
        }
        return $output;
    }

    /**
     * Builds a typed map in which later duplicate keys replace earlier entries.
     *
     * @param string $keyColumn Selected column providing each map key.
     * @param string $valueColumn Selected column providing each map value.
     * @param Lucinda\SQL\DataType $dataTypeKey Expected map key type, restricted to integers or strings.
     * @param Lucinda\SQL\DataType $dataTypeValue Expected type of each non-null mapped value.
     * @return array<int|string, mixed> Values indexed by normalized keys.
     */
    public function toMap(
        string $keyColumn,
        string $valueColumn,
        DataType $dataTypeKey,
        DataType $dataTypeValue
        ): array
    {
        if ($dataTypeKey !== DataType::STRING && $dataTypeKey !== DataType::INTEGER) {
            throw new UnexpectedValueException("Only integers and strings are allowed to be keys");
        }
        $output = [];
        while ($row = $this->statementResults->toRow()) {
            if (!array_key_exists($keyColumn, $row)) {
                throw new UnexpectedValueException("Row contains no key column");
            }
            if (!array_key_exists($valueColumn, $row)) {
                throw new UnexpectedValueException("Row contains no value column");
            }
            $key = $row[$keyColumn];
            $value = $row[$valueColumn];
            $key = $this->validator->validate($key, $dataTypeKey);
            if ($value !== null) {
                $value = $this->validator->validate($value, $dataTypeValue);
            }
            $output[$key] = $value;
        }
        return $output;
    }

    /**
     * Wraps the next projection as a typed row, returning null at end of results.
     *
     * @return ?Lucinda\Framework\Persistence\Builders\Row
     */
    public function toRow(): ?Row
    {
        $row = $this->statementResults->toRow();
        return $row === null ? null : new Row($row, $this->validator);
    }

    /**
     * Traverses the remaining result set and retains every projection as a typed row.
     *
     * @return list<\Lucinda\Framework\Persistence\Builders\Row> Rows in result-set order.
     */
    public function toList(): array
    {
        $output = [];
        while ($row = $this->statementResults->toRow()) {
            $output[] = new Row($row, $this->validator);
        }
        return $output;
    }
}

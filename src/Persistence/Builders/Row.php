<?php

namespace Lucinda\Framework\Persistence\Builders;

use Lucinda\Framework\Persistence\Queries\ParametersValidator;
use Lucinda\Framework\Persistence\Queries\ValidationException;
use Lucinda\SQL\DataType;

/**
 * Reads typed row from one projection through the injected validator.
 *
 * SQL NULL is preserved for every accessor. Missing columns and malformed
 * non-null values are reported as unexpected stored data.
 *
 * @internal
 */
final class Row
{
    /** @var array<string, mixed> Selected values indexed by column name */
    private array $row;
    private ParametersValidator $validator;

    /**
     * @param array<string, mixed> $row Selected projection
     * @param ParametersValidator $validator Column validation and normalization
     */
    public function __construct(array $row, ParametersValidator $validator)
    {
        $this->row = $row;
        $this->validator = $validator;
    }

    /**
     * Reads a selected column using its expected SQL type.
     *
     * @throws ValidationException If the column is missing or malformed
     */
    private function get(string $key, DataType $dataType): mixed
    {
        if (!array_key_exists($key, $this->row)) {
            throw new ValidationException(
                '',
                '',
                $key
            );
        }

        $value = $this->row[$key];
        if ($value === null) {
            return null;
        }
        try {
            return $this->validator->validate($value, $dataType);
        } catch (ValidationException $e) {
            throw new ValidationException(
                $e->getExpectedValueType(),
                $e->getActualValueType(),
                $key
            );
        }
    }

    /**
     * Normalizes a string-compatible value without changing SQL NULL.
     *
     * @param string $key Selected column or named parameter identifier.
     * @return ?string
     * @throws \Lucinda\Framework\Persistence\Queries\ValidationException If the value cannot satisfy the expected type.
     */
    public function getString(string $key): ?string
    {
        return $this->get($key, DataType::STRING);
    }

    /**
     * Normalizes an integer or integer string without changing SQL NULL.
     *
     * @param string $key Selected column or named parameter identifier.
     * @return ?int
     * @throws \Lucinda\Framework\Persistence\Queries\ValidationException If the value cannot satisfy the expected type.
     */
    public function getInteger(string $key): ?int
    {
        return $this->get($key, DataType::INTEGER);
    }

    /**
     * Normalizes the explicitly supported boolean representations.
     *
     * @param string $key Selected column or named parameter identifier.
     * @return ?bool
     * @throws \Lucinda\Framework\Persistence\Queries\ValidationException If the value cannot satisfy the expected type.
     */
    public function getBoolean(string $key): ?bool
    {
        return $this->get($key, DataType::BOOLEAN);
    }
    /**
     * Returns binary bytes or an open stream without consuming the content.
     *
     * @param string $key Selected column or named parameter identifier.
     * @return resource|string|null Binary content in its existing representation.
     * @throws \Lucinda\Framework\Persistence\Queries\ValidationException If the value cannot satisfy the expected type.
     */
    public function getStreamed(string $key): mixed
    {
        if (
            array_key_exists($key, $this->row)
            && is_string($this->row[$key])
        ) {
            return $this->row[$key];
        }

        return $this->get($key, DataType::STREAMED);
    }

    /**
     * Converts a numeric column to a floating-point value, preserving SQL NULL.
     *
     * @param string $key Selected column or named parameter identifier.
     * @return ?float
     * @throws \Lucinda\Framework\Persistence\Queries\ValidationException If the value cannot satisfy the expected type.
     */
    public function getFloat(string $key): ?float
    {
        $value = $this->get($key, DataType::STRING);
        if ($value === null) {
            return null;
        }
        if (is_numeric($value)) {
            return (float) $value;
        } else {
            throw new ValidationException(
                'float',
                get_debug_type($value),
                $key
            );
        }
    }
}

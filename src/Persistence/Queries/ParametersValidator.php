<?php

namespace Lucinda\Framework\Persistence\Queries;

use Lucinda\SQL\DataType;

/**
 * Normalizes supported input values according to explicit SQL binding types.
 */
final class ParametersValidator
{
    /**
     * Validates and normalizes a value for the explicitly requested binding type.
     *
     * @param mixed $value Value to validate without consuming stream content.
     * @param Lucinda\SQL\DataType $dataType Explicit expected SQL value type.
     * @return mixed
     * @throws \Lucinda\Framework\Persistence\Queries\ValidationException If the value cannot satisfy the expected type.
     */
    public function validate(mixed $value, DataType $dataType): mixed
    {
        if ($dataType === DataType::BOOLEAN) {
            return $this->getBoolean($value);
        } elseif ($dataType === DataType::INTEGER) {
            return $this->getInteger($value);
        } elseif ($dataType === DataType::NULLABLE) {
            return $this->getNull($value);
        } elseif ($dataType === DataType::STRING) {
            return $this->getString($value);
        } else {
            return $this->getStream($value);
        }
    }

    /**
     * Accepts booleans and the supported zero/one or true/false spellings.
     *
     * @param mixed $value Value to validate without consuming stream content.
     * @return bool
     * @throws \Lucinda\Framework\Persistence\Queries\ValidationException If the value cannot satisfy the expected type.
     */
    private function getBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        } else if (is_int($value)) {
            if ($value === 0 || $value === 1) {
                return (bool) $value;
            }
        } else if (is_string($value)) {
            if ($value === '0' || $value === '1') {
                return $value === '1';
            } else if ($value==='true' || $value==='false') {
                return $value === 'true';
            }
        }
        throw new ValidationException('boolean', get_debug_type($value));
    }

    /**
     * Accepts integers and integer strings within the platform range.
     *
     * @param mixed $value Value to validate without consuming stream content.
     * @return int
     * @throws \Lucinda\Framework\Persistence\Queries\ValidationException If the value cannot satisfy the expected type.
     */
    private function getInteger(mixed $value): int
    {
        if (is_int($value)) {
            return $value;
        } else if (is_string($value)) {
            $integer = filter_var($value, FILTER_VALIDATE_INT);
            if ($integer !== false) {
                return $integer;
            }
        }

        throw new ValidationException('integer', get_debug_type($value));
    }

    /**
     * Requires an actual null for a null input binding.
     *
     * @param mixed $value Value to validate without consuming stream content.
     * @return null
     * @throws \Lucinda\Framework\Persistence\Queries\ValidationException If the value cannot satisfy the expected type.
     */
    private function getNull(mixed $value): null
    {
        if ($value === null) {
            return $value;
        }
        throw new ValidationException('null', get_debug_type($value));
    }

    /**
     * Preserves strings and converts numeric scalars to strings.
     *
     * @param mixed $value Value to validate without consuming stream content.
     * @return string
     * @throws \Lucinda\Framework\Persistence\Queries\ValidationException If the value cannot satisfy the expected type.
     */
    private function getString(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        } else if (is_int($value) || is_float($value)) {
            return (string) $value;
        }
        throw new ValidationException('string', get_debug_type($value));
    }

    /**
     * Requires an open stream resource without reading its content.
     *
     * @param mixed $value Value to validate without consuming stream content.
     * @return mixed
     * @throws \Lucinda\Framework\Persistence\Queries\ValidationException If the value cannot satisfy the expected type.
     */
    private function getStream(mixed $value): mixed
    {
        if (is_resource($value) && get_resource_type($value) === 'stream') {
            return $value;
        }
        throw new ValidationException('stream', get_debug_type($value));
    }
}

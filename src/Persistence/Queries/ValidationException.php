<?php

namespace Lucinda\Framework\Persistence\Queries;

/**
 * Describes expected and actual types, with optional column context.
 */
final class ValidationException extends \Exception
{
    private string $expectedValueType, $actualValueType;

    /**
     * Describes an invalid value or a missing column using type metadata.
     *
     * @param string $expectedValueType Expected value type; empty with an empty actual type denotes a missing column.
     * @param string $actualValueType Actual value type reported without including its contents.
     * @param ?string $key Selected column or named parameter identifier.
     */
    public function __construct(string $expectedValueType, string $actualValueType, ?string $key = null)
    {
        if ($key === null) {
            parent::__construct('Expected '.$expectedValueType.', got '.$actualValueType); 
        } else {
            if ($expectedValueType!=='' || $actualValueType!=='') {
                parent::__construct('Key '.$key.' expects '.$expectedValueType.', got '.$actualValueType); 
            } else {
                parent::__construct('Column '.$key.' is missing');
            }
        }
        $this->expectedValueType = $expectedValueType;
        $this->actualValueType = $actualValueType;
    }

    /**
     * Returns the expected type recorded by this validation failure.
     *
     * @return string
     */
    public function getExpectedValueType(): string
    {
        return $this->expectedValueType;
    }

    /**
     * Returns the actual type recorded without exposing the invalid value.
     *
     * @return string
     */
    public function getActualValueType(): string
    {
        return $this->actualValueType;
    }
}

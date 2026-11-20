<?php

namespace Lucinda\Framework\Persistence\Queries;

use Lucinda\SQL\DataType;

/**
 * Stores validated SQL parameter values together with their explicit binding types.
 */
final class Parameters
{
    /** @var array<string, array{value: mixed, type: DataType}> */
    private array $storage = [];
    private ParametersValidator $validator;

    /**
     * Retains the validator used before each parameter is stored.
     *
     * @param Lucinda\Framework\Persistence\Queries\ParametersValidator $validator Validator retained for subsequent value normalization.
     */
    public function __construct(ParametersValidator $validator = new ParametersValidator())
    {
        $this->validator = $validator;
    }

    /**
     * Validates and normalizes a parameter before storing or replacing its binding.
     *
     * @param string $key Selected column or named parameter identifier.
     * @param mixed $value Value to validate without consuming stream content.
     * @param Lucinda\SQL\DataType $dataType Explicit expected SQL value type.
     * @return Lucinda\Framework\Persistence\Queries\Parameters
     * @throws ValidationException If validation fails; existing storage is unchanged.
     */
    public function set(string $key, mixed $value, DataType $dataType): Parameters
    {
        $this->storage[$key] = [
            "value"=>$this->validator->validate($value, $dataType),
            "type"=>$dataType
            ];
        return $this;
    }

    /** @return array<string, array{value: mixed, type: DataType}> */
    public function getStorage(): array
    {
        return $this->storage;
    }
}

<?php

namespace Lucinda\Framework\NoSQL;

use Lucinda\NoSQL\OperationFailedException;

/**
 * Validates and decodes the representation of one atomic storage record
 *
 * Invalid storage content is an operation failure rather than a missing key.
 * Object instantiation is disabled when decoding the serialized payload.
 *
 * @internal
 */
final class StoredValueBuilder
{
    /** Encoded record retained for this conversion */
    private string $source;

    /**
     * Captures the bytes returned by a native adapter
     *
     * @param string $source Complete encoded atomic record
     */
    public function __construct(string $source)
    {
        $this->source = $source;
    }

    /**
     * Builds a validated record without executing any storage operation
     *
     * @return StoredValue Decoded metadata and supported payload
     * @throws OperationFailedException If stored content is malformed
     */
    public function build(): StoredValue
    {
        try {
            $data = json_decode($this->source, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($data) || $this->isDataInvalid($data)) {
                throw new \InvalidArgumentException('Invalid record fields');
            }
            
            $bytes = base64_decode($data['payload'], true);
            if ($bytes === false) {
                throw new \InvalidArgumentException('Invalid payload bytes');
            }

            $value = @unserialize($bytes, [
                'allowed_classes' => false,
                'max_depth' => 128,
            ]);
            if ($value === false && $bytes !== 'b:0;') {
                throw new \InvalidArgumentException('Invalid payload value');
            }

            $record = new StoredValue(
                $data['version'],
                (float) $data['expires'],
                $value,
                $data['deleted']
            );
            $record->validate();

            return $record;
        } catch (\JsonException | \InvalidArgumentException $error) {
            throw new OperationFailedException(
                'Invalid atomic storage record',
                0,
                $error
            );
        }
    }

    /**
     * Detects missing or unsupported metadata before payload decoding
     * 
     * @param array $data
     * @return bool True when encoded record metadata is invalid.
     */
    private function isDataInvalid(array $data): bool
    {
        return (
            ($data['format'] ?? null) !== 1
            || !is_string($data['version'] ?? null)
            || !is_bool($data['deleted'] ?? null)
            || !is_string($data['payload'] ?? null)
            || (!is_int($data['expires'] ?? null)
                && !is_float($data['expires'] ?? null))
        );
    }
}

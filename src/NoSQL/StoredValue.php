<?php

namespace Lucinda\Framework\NoSQL;

/**
 * Owns the revision, deadline and payload encoded in one atomic storage record
 *
 * Binary strings are preserved through a base64-encoded serialized payload.
 * Values are restricted to scalars, null and arrays, avoiding object hooks.
 *
 * @internal
 */
final class StoredValue
{
    /** Unique revision changed by every accepted mutation */
    private string $version;

    /** Logical expiration in Unix seconds; zero means no expiration */
    private float $expiresAt;

    /** Application value retained by this record */
    private mixed $value;

    /** Whether this record represents a conditional deletion */
    private bool $deleted;

    /**
     * Captures the fields of one complete atomic record
     *
     * @param string $version Unique logical revision
     * @param float $expiresAt Absolute expiration or zero
     * @param mixed $value Supported application value
     * @param bool $deleted Whether this is a deletion tombstone
     */
    public function __construct(
        string $version,
        float $expiresAt,
        mixed $value,
        bool $deleted = false
    ) {
        $this->version = $version;
        $this->expiresAt = $expiresAt;
        $this->value = $value;
        $this->deleted = $deleted;
    }

    /**
     * Gets the logical revision associated with these bytes
     *
     * @return string Unique revision
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * Gets the payload's original expiration
     *
     * @return float Absolute Unix expiration or zero
     */
    public function getExpiresAt(): float
    {
        return $this->expiresAt;
    }

    /**
     * Gets the application value retained in the record
     *
     * @return mixed Supported payload value
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * Checks whether this record currently represents a live value
     *
     * @return bool False for tombstones and expired records
     */
    public function isLive(): bool
    {
        return !$this->deleted && ($this->expiresAt === 0.0
            || $this->expiresAt > microtime(true));
    }

    /**
     * Validates metadata and every nested payload value
     *
     * @throws \InvalidArgumentException If metadata or payload is unsupported
     */
    public function validate(): void
    {
        if (
            $this->version === '' || !is_finite($this->expiresAt)
            || $this->expiresAt < 0
        ) {
            throw new \InvalidArgumentException('Invalid atomic metadata');
        }
        $this->validateValue($this->value, 0);
    }

    /**
     * Rejects objects, resources and recursive or excessively nested arrays
     *
     * @param mixed $value Value reached within this payload
     * @param int $depth Current nesting depth
     * @throws \InvalidArgumentException If the value cannot be stored safely
     */
    private function validateValue(mixed $value, int $depth): void
    {
        if (
            $depth > 64 || (!is_scalar($value) && $value !== null
                && !is_array($value))
        ) {
            throw new \InvalidArgumentException('Unsupported atomic value');
        }
        if (is_array($value)) {
            foreach ($value as $item) {
                $this->validateValue($item, $depth + 1);
            }
        }
    }

    /**
     * Encodes the complete record without losing binary payload bytes
     *
     * @return string Storage representation understood only by the adapters
     * @throws \InvalidArgumentException If metadata or payload is invalid
     * @throws \JsonException If encoding fails
     */
    public function encode(): string
    {
        $this->validate();
        return json_encode([
            'format' => 1,
            'version' => $this->version,
            'expires' => $this->expiresAt,
            'deleted' => $this->deleted,
            'payload' => base64_encode(serialize($this->value)),
        ], JSON_THROW_ON_ERROR);
    }
}

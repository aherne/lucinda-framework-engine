<?php

namespace Lucinda\Framework\NoSQL;

use Lucinda\NoSQL\KeyNotFoundException;

/**
 * Captures the logical value and version observed by one atomic adapter
 *
 * This immutable observation contains no connection or native comparison
 * token. Its issuing adapter alone owns the corresponding storage metadata.
 */
final readonly class VersionedValue
{
    /** Logical key observed inside the adapter's reserved namespace */
    private string $key;

    /** Observed application value, including a legitimate null value */
    private mixed $value;

    /** Opaque logical revision, null only for an absent observation */
    private ?string $version;

    /** Absolute expiration in Unix seconds; zero means no expiration */
    private float $expiresAt;

    /**
     * Captures the logical observation assembled by an atomic adapter
     *
     * @param string $key Logical storage identity
     * @param mixed $value Observed value
     * @param string|null $version Opaque revision or absence
     * @param float $expiresAt Absolute expiration or zero
     */
    public function __construct(
        string $key,
        mixed $value,
        ?string $version,
        float $expiresAt
    ) {
        $this->key = $key;
        $this->value = $value;
        $this->version = $version;
        $this->expiresAt = $expiresAt;
    }

    /**
     * Gets the logical identity that was observed
     *
     * @return string Key without the adapter's internal namespace
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * Checks whether a live value existed when this observation was captured
     *
     * @return bool True even when the stored value itself was null or false
     */
    public function exists(): bool
    {
        return $this->version !== null;
    }

    /**
     * Gets the value captured by this observation
     *
     * @return mixed Scalar, null or array owned by this observation
     * @throws KeyNotFoundException If no live value was observed
     */
    public function getValue(): mixed
    {
        if (!$this->exists()) {
            throw new KeyNotFoundException($this->key);
        }
        return $this->value;
    }

    /**
     * Gets the opaque logical revision without exposing vendor metadata
     *
     * @return string|null Revision or null for an absent observation
     */
    public function getVersion(): ?string
    {
        return $this->version;
    }

    /**
     * Gets the original expiration retained by counter operations
     *
     * @return float Absolute Unix expiration, zero for no expiration
     */
    public function getExpiresAt(): float
    {
        return $this->expiresAt;
    }
}

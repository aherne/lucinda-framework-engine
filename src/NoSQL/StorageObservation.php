<?php

namespace Lucinda\Framework\NoSQL;

/**
 * Retains the native comparison metadata owned by an atomic adapter
 *
 * This object stays inside the integration layer. Public versioned values
 * never expose its vendor-specific token or encoded storage representation.
 *
 * @internal
 */
final class StorageObservation
{
    /** Physical key inside the adapter's namespace */
    private string $key;

    /** Encoded stored record or null for native absence */
    private ?string $value;

    /** Vendor comparison token or null for native absence */
    private int|float|string|null $token;

    /**
     * Captures one native observation without retaining a live cursor
     *
     * @param string $key Physical storage identity
     * @param string|null $value Encoded record bytes
     * @param int|float|string|null $token Native comparison metadata
     */
    public function __construct(
        string $key,
        ?string $value,
        int|float|string|null $token
    ) {
        $this->key = $key;
        $this->value = $value;
        $this->token = $token;
    }

    /**
     * Gets the physical key used by the native operation
     *
     * @return string Physical storage identity
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * Gets the encoded record captured by the native read
     *
     * @return string|null Original bytes or absence
     */
    public function getValue(): ?string
    {
        return $this->value;
    }

    /**
     * Gets native metadata interpreted only by the matching adapter
     *
     * @return int|float|string|null Opaque vendor comparison token
     */
    public function getToken(): int|float|string|null
    {
        return $this->token;
    }
}

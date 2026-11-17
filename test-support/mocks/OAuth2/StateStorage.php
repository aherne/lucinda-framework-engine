<?php

namespace Test\Lucinda\Framework\mocks\OAuth2;

final class StateStorage implements \Lucinda\Framework\OAuth2\StateStorage
{
    public array $saved = [];
    public array $consumed = [];
    public bool $accepted = true;
    public ?\Throwable $failure = null;

    public function save(string $state, string $vendorName, int $validUntil, string $browserProofHash): void
    {
        if ($this->failure) {
            throw $this->failure;
        }
        $this->saved[] = [$state, $vendorName, $validUntil, $browserProofHash];
    }

    public function consume(string $state, string $vendorName, string $browserProofHash): bool
    {
        if ($this->failure) {
            throw $this->failure;
        }
        $this->consumed[] = [$state, $vendorName, $browserProofHash];
        return $this->accepted;
    }

    public function clearExpired(): void
    {
    }
}

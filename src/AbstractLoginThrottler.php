<?php

namespace Lucinda\Framework;

use Lucinda\WebSecurity\DAO\LoginThrottler;

/**
 * Basic time based throttler against serial failed login attempts:
 * - unit of verification is ip address and username
 * - each failed login is penalized for two power attempts number seconds penalty
 * - if another login comes in penalty period, user automatically gets LOGIN_FAILED status
 */
abstract class AbstractLoginThrottler implements LoginThrottler
{
    public const PENALTY_QUOTIENT = 2;

    private int $attempts = 0;
    private ?string $penaltyExpiration = null;

    /**
     * {@inheritDoc}
     *
     * @see LoginThrottler::getTimePenalty()
     */
    public function getTimePenalty(): int
    {
        return ($this->penaltyExpiration && strtotime($this->penaltyExpiration) > time() ? strtotime($this->penaltyExpiration)-time() : 0);
    }

    /**
     * {@inheritDoc}
     *
     * @see LoginThrottler::setFailure()
     */
    public function setFailure(): void
    {
        $this->attempts++;
        $this->penaltyExpiration = ($this->attempts>1 ? date("Y-m-d H:i:s", time() + pow(self::PENALTY_QUOTIENT, $this->attempts-1)) : null);
        $this->persist($this->attempts, $this->penaltyExpiration);
    }

    /**
     * {@inheritDoc}
     *
     * @see LoginThrottler::setSuccess()
     */
    public function setSuccess(): void
    {
        $this->attempts = 0;
        $this->penaltyExpiration = null;
        $this->persist($this->attempts, $this->penaltyExpiration);
    }

    /**
     * Persists throttling info in database
     */
    abstract protected function persist(): void;

    // public function penalize(string $userName, string $ipAddress): void
    // {
    //     $key = $userName."@".$ipAddress;
    //     $this->attempts[$key] = ($this->attempts[$key] ?? 0) + 1;
    // }

    // public function isStopped(string $userName, string $ipAddress): bool
    // {
    //     return ($this->attempts[$userName."@".$ipAddress] ?? 0) >= 3;
    // }
}

<?php
namespace Lucinda\Framework;

use Lucinda\WebSecurity\Authentication\Form\LoginThrottler;

/**
 * Basic time based throttler against serial failed login attempts:
 * - unit of verification is ip address and username
 * - each failed login is penalized for two power attempts number seconds penalty
 * - if another login comes in penalty period, user automatically gets LOGIN_FAILED status
 */
abstract class AbstractLoginThrottler extends LoginThrottler
{
    const PENALTY_QUOTIENT = 2;
    
    protected int $attempts = 0;
    protected ?string $penaltyExpiration = null;
    
    /**
     * {@inheritDoc}
     * @see LoginThrottler::getTimePenalty()
     */
    public function getTimePenalty(): int
    {
        return ($this->penaltyExpiration && strtotime($this->penaltyExpiration) > time()?strtotime($this->penaltyExpiration)-time():0);
    }
    
    /**
     * {@inheritDoc}
     * @see LoginThrottler::setFailure()
     */
    public function setFailure(): void
    {
        $this->attempts++;
        $this->penaltyExpiration = ($this->attempts>1?date("Y-m-d H:i:s", time() + pow(self::PENALTY_QUOTIENT, $this->attempts-1)):null);
        $this->persist();
    }
    
    /**
     * {@inheritDoc}
     * @see LoginThrottler::setSuccess()
     */
    public function setSuccess(): void
    {
        $this->attempts = 0;
        $this->penaltyExpiration = null;
        $this->persist();
    }
    
    /**
     * Persists throttling info in database
     */
    abstract protected function persist(): void;
}

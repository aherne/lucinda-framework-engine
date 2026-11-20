<?php

namespace Lucinda\Framework\Persistence;

use Lucinda\Framework\Persistence\ThrottleBucket;
use Lucinda\Framework\Persistence\Builders\Row;
use Lucinda\Framework\Persistence\Queries\ParametersValidator;

/**
 * Owns the NoSQL failure-window policy for one authentication scope
 *
 * Conditional replacement increments or resets a complete window atomically.
 * The selected backend owns vendor operations; this class owns the policy.
 *
 * @internal
 */
final class NoSqlThrottle
{
    /** Executor used for this scope's counter operations */
    private NoSqlExecutor $executor;

    /** Authentication scope included in each bucket */
    private string $scope;

    /** Failure threshold for one active window */
    private int $limit;

    /** Fixed failure-window lifetime in seconds */
    private int $period;

    /**
     * Captures the executor and policy for one authentication scope
     *
     * @param NoSqlExecutor $executor Connection and keyspace for this scope
     * @param string $scope Authentication operation name
     * @param int $limit Failure threshold for one window
     * @param int $period Window lifetime in seconds
     * @throws \InvalidArgumentException If the policy contains invalid values
     */
    public function __construct(
        NoSqlExecutor $executor,
        string $scope,
        int $limit = 5,
        int $period = 900
    ) {
        if ($limit < 1 || $period < 1) {
            throw new \InvalidArgumentException('Invalid throttle policy');
        }
        $this->executor = $executor;
        $this->scope = $scope;
        $this->limit = $limit;
        $this->period = $period;
    }

    /**
     * Resolves one subject's namespaced failure-window identity
     *
     * @param string $subject Submitted identity for this scope
     * @param string $ip Client address supplied by the request
     * @return string Application-scoped counter key
     */
    private function getKey(string $subject, string $ip): string
    {
        $bucket = new ThrottleBucket($this->scope, $subject, $ip);
        return $this->executor->getKeyspace()->getKey(
            'throttle',
            $bucket->getKey()
        );
    }

    /**
     * Atomically records one rejected attempt without extending a live window
     *
     * @param string $subject Submitted identity for this scope
     * @param string $ip Client address supplied by the request
     * @throws \RuntimeException If contention prevents recording the attempt
     * @throws \Throwable If storage or record validation fails
     */
    public function penalize(string $subject, string $ip): void
    {
        $key = $this->getKey($subject, $ip);
        for ($attempt = 0; $attempt < 1000; $attempt++) {
            $snapshot = $this->executor->read($key);
            $values = ($snapshot->exists() ? $snapshot->getValue() : []);
            $now = time();
            $expires = $now + $this->period;
            $count = 1;
            if ($values !== []) {
                $row = new Row($values, new ParametersValidator());
                $storedExpires = $row->getInteger('expires')
                    ?? throw new \UnexpectedValueException('Null throttle expiry');
                $storedCount = $row->getInteger('attempts')
                    ?? throw new \UnexpectedValueException('Null throttle attempts');
                if ($storedExpires > $now) {
                    $expires = $storedExpires;
                    $count = min($storedCount + 1, 1000000);
                }
            }
            if (
                $this->executor->replace($snapshot, [
                'attempts' => $count,
                'expires' => $expires,
                ], max(1, $expires - $now))
            ) {
                return;
            }
        }
        throw new \RuntimeException('Throttle contention exceeded limit');
    }

    /**
     * Checks whether this scope's live failure threshold has been reached
     *
     * @param string $subject Submitted identity for this scope
     * @param string $ip Client address supplied by the request
     * @return bool True when the live failure window blocks the attempt
     * @throws \Throwable If storage or record validation fails
     */
    public function blocked(string $subject, string $ip): bool
    {
        $snapshot = $this->executor->read($this->getKey($subject, $ip));
        $values = ($snapshot->exists() ? $snapshot->getValue() : []);
        if ($values === []) {
            return false;
        }
        $row = new Row($values, new ParametersValidator());
        $expires = $row->getInteger('expires')
            ?? throw new \UnexpectedValueException('Null throttle expiry');
        if ($expires <= time()) {
            return false;
        }
        $attempts = $row->getInteger('attempts')
            ?? throw new \UnexpectedValueException('Null throttle attempts');
        return $attempts >= $this->limit;
    }
}

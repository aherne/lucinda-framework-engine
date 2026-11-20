<?php

namespace Lucinda\Framework\NoSQL;

use Lucinda\Framework\Persistence\Builders\Row;
use Lucinda\Framework\Persistence\Queries\ParametersValidator;
use Lucinda\Framework\Persistence\NoSqlExecutor;

/**
 * Retains the first-observed timestamp of a rendered representation
 *
 * Atomic initialization gives concurrent requests the same timestamp while
 * the retained representation remains live on the selected backend.
 *
 * @internal
 */
final class RepresentationStore
{
    /** Executor used for representation record operations */
    private NoSqlExecutor $executor;

    /** Retention lifetime of one representation */
    private int $lifetime;

    /**
     * Captures the connection and representation retention policy
     *
     * @param NoSqlExecutor $executor Shared connection and namespace
     * @param int $lifetime Positive retention lifetime in seconds
     * @throws \InvalidArgumentException If the lifetime is not positive
     */
    public function __construct(
        NoSqlExecutor $executor,
        int $lifetime = 86400
    ) {
        if ($lifetime < 1) {
            throw new \InvalidArgumentException('Invalid retention lifetime');
        }
        $this->executor = $executor;
        $this->lifetime = $lifetime;
    }

    /**
     * Reads or atomically establishes the representation's observation time
     *
     * @param string $identity Host and rendered representation identity
     * @return int Unix timestamp of the retained first observation
     * @throws \RuntimeException If excessive contention prevents initialization
     * @throws \Throwable If storage or validation fails
     */
    public function getTime(string $identity): int
    {
        $key = $this->executor->getKeyspace()->getKey(
            'representation',
            $identity
        );
        for ($attempt = 0; $attempt < 1000; $attempt++) {
            $snapshot = $this->executor->read($key);
            $values = ($snapshot->exists() ? $snapshot->getValue() : []);
            $now = time();
            if ($values !== []) {
                $row = new Row($values, new ParametersValidator());
                $expires = $row->getInteger('expires')
                    ?? throw new \UnexpectedValueException('Null representation expiry');
                if ($expires > $now) {
                    return $row->getInteger('time')
                        ?? throw new \UnexpectedValueException('Null representation time');
                }
            }
            if (
                $this->executor->replace($snapshot, [
                'time' => $now,
                'expires' => $now + $this->lifetime,
                ], $this->lifetime)
            ) {
                return $now;
            }
        }
        throw new \RuntimeException('Representation contention exceeded limit');
    }
}

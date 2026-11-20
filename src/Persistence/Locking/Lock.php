<?php

namespace Lucinda\Framework\Persistence\Locking;

/**
 * Coordinates ownership of one identity according to the storage lifecycle
 *
 * Reacquiring the same identity through one lock owner is idempotent.
 * Switching identities releases the previous lock before acquisition.
 * Connection-owned locks end on release or disconnection. Leased locks can
 * expire sooner and must reject stale owners when protecting stored writes.
 */
interface Lock
{
    /**
     * Acquires exclusive ownership of the requested identity
     *
     * @param string $identity Identity to protect for this operation
     * @throws \Throwable If exclusive ownership cannot be established
     */
    public function acquire(string $identity): void;

    /**
     * Releases the identity currently owned by this lock instance
     *
     * @throws \Throwable If an owned lock cannot be released
     */
    public function release(): void;
}

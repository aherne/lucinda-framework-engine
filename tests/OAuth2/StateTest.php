<?php

namespace Test\Lucinda\Framework\OAuth2;

use Lucinda\Framework\OAuth2\State;
use Lucinda\STDOUT\Cookies;
use Lucinda\UnitTest\Result;
use Test\Lucinda\Framework\mocks\OAuth2\BrowserProofCookies;
use Test\Lucinda\Framework\mocks\OAuth2\StateStorage;

final class StateTest
{
    public function save(): array
    {
        return $this->withCookies(function (State $state, StateStorage $storage): array {
            $expires = time() + 600;
            $state->save("state-one", "Google", $expires);
            $state->save("state-two", "Github", $expires);
            [$name, $proof, $options] = BrowserProofCookies::$writes[0];
            return [
                new Result($state instanceof \Lucinda\WebSecurity\OAuth2State, "security interface"),
                new Result($name === $this->cookieName("state-one"), "host-prefixed state hash"),
                new Result(preg_match('/\A[0-9a-f]{64}\z/', $proof) === 1, "256-bit browser proof"),
                new Result($storage->saved[0] === ["state-one", "Google", $expires, hash("sha256", $proof)], "persists proof hash and provider"),
                new Result($options === ["expires" => $expires, "path" => "/", "secure" => true, "httponly" => true, "samesite" => "Lax"], "secure host-only cookie attributes"),
                new Result(BrowserProofCookies::$writes[1][0] !== $name && BrowserProofCookies::$writes[1][1] !== $proof, "independent login attempts"),
                new Result($_COOKIE === [], "outgoing cookie does not alter incoming cookies")
            ];
        });
    }

    public function consume(): array
    {
        return $this->withCookies(function (State $state, StateStorage $storage): array {
            $proof = str_repeat("ab", 32);
            $name = $this->cookieName("callback-state");
            $_COOKIE[$name] = $proof;
            $before = time();
            $accepted = $state->consume("callback-state", "Google");
            [$deletedName, $value, $options] = BrowserProofCookies::$writes[0];
            return [
                new Result($accepted, "successful storage consumption is accepted"),
                new Result($storage->consumed === [["callback-state", "Google", hash("sha256", $proof)]], "delegates state, provider and proof hash"),
                new Result($deletedName === $name && $value === "", "expires matching proof cookie"),
                new Result($options["expires"] >= $before - 3600 && $options["expires"] <= time() - 3600, "cookie deletion expiration"),
                new Result(array_diff_key($options, ["expires" => true]) === ["path" => "/", "secure" => true, "httponly" => true, "samesite" => "Lax"], "deletion retains security attributes")
            ];
        });
    }

    public function rejectInvalidProof(): array
    {
        return $this->withCookies(function (State $state, StateStorage $storage): array {
            $results = [new Result(!$state->consume("callback-state", "Google"), "missing cookie")];
            foreach ([null, [], 123, "", str_repeat("a", 63), str_repeat("a", 65), str_repeat("G", 64), str_repeat("A", 64), str_repeat("a", 64)."\n"] as $proof) {
                $_COOKIE[$this->cookieName("callback-state")] = $proof;
                $results[] = new Result(!$state->consume("callback-state", "Google"), "malformed cookie: ".get_debug_type($proof));
            }
            $results[] = new Result($storage->consumed === [] && BrowserProofCookies::$writes === [], "invalid proofs never reach storage or delete cookies");
            return $results;
        });
    }

    public function rejectUnconsumedState(): array
    {
        return $this->withCookies(function (State $state, StateStorage $storage): array {
            $_COOKIE[$this->cookieName("callback-state")] = str_repeat("a", 64);
            $storage->accepted = false;
            return [
                new Result(!$state->consume("callback-state", "OtherProvider"), "storage rejection is returned"),
                new Result(count($storage->consumed) === 1 && BrowserProofCookies::$writes === [], "rejected callback retains cookie for retry")
            ];
        });
    }

    public function rejectExpiredSave(): array
    {
        return $this->withCookies(function (State $state, StateStorage $storage): array {
            $results = [];
            foreach ([time(), time() - 60] as $expires) {
                $results[] = new Result($this->throws(fn () => $state->save("state", "Google", $expires), \InvalidArgumentException::class), "rejects non-future expiration");
            }
            $results[] = new Result($storage->saved === [] && BrowserProofCookies::$writes === [], "expired attempts have no side effects");
            return $results;
        });
    }

    public function rejectSentHeaders(): array
    {
        return $this->withCookies(function (State $state, StateStorage $storage): array {
            BrowserProofCookies::$headersSent = true;
            return [
                new Result($this->throws(fn () => $state->save("state", "Google", time() + 600), \RuntimeException::class), "headers already sent"),
                new Result($storage->saved === [] && BrowserProofCookies::$writes === [], "checks headers before persistence")
            ];
        });
    }

    public function storageFailures(): array
    {
        return $this->withCookies(function (State $state, StateStorage $storage): array {
            $failure = new \RuntimeException("storage unavailable");
            $storage->failure = $failure;
            $_COOKIE[$this->cookieName("state")] = str_repeat("a", 64);
            $results = [];
            foreach ([fn () => $state->save("state", "Google", time() + 600), fn () => $state->consume("state", "Google")] as $operation) {
                try {
                    $operation();
                    $results[] = new Result(false, "storage failure was swallowed");
                } catch (\Throwable $exception) {
                    $results[] = new Result($exception === $failure, "propagates storage failure");
                }
            }
            $results[] = new Result(BrowserProofCookies::$writes === [], "storage failures do not send cookies");
            return $results;
        });
    }

    public function cookieFailures(): array
    {
        return $this->withCookies(function (State $state, StateStorage $storage): array {
            BrowserProofCookies::$writeSucceeds = false;
            $_COOKIE[$this->cookieName("state")] = str_repeat("a", 64);
            return [
                new Result($this->throws(fn () => $state->save("state", "Google", time() + 600), \RuntimeException::class), "cookie creation failure throws"),
                new Result(count($storage->saved) === 1, "persists before cookie creation"),
                new Result($this->throws(fn () => $state->consume("state", "Google"), \RuntimeException::class), "cookie deletion failure throws"),
                new Result(count($storage->consumed) === 1, "consumes before cookie deletion")
            ];
        });
    }

    private function cookieName(string $state): string
    {
        return "__Host-lucinda_oauth2_".hash("sha256", $state);
    }

    private function throws(callable $operation, string $exceptionClass): bool
    {
        try {
            $operation();
            return false;
        } catch (\Throwable $exception) {
            return $exception instanceof $exceptionClass;
        }
    }

    private function withCookies(callable $test): array
    {
        $cookies = $_COOKIE;
        BrowserProofCookies::$enabled = true;
        BrowserProofCookies::$headersSent = false;
        BrowserProofCookies::$writeSucceeds = true;
        BrowserProofCookies::$writes = [];
        $_COOKIE = [];
        try {
            return $test(new State($storage = new StateStorage(), new Cookies()), $storage);
        } finally {
            $_COOKIE = $cookies;
            BrowserProofCookies::$enabled = false;
            BrowserProofCookies::$writes = [];
        }
    }
}

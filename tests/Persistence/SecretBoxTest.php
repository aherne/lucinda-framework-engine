<?php

namespace Test\Lucinda\Framework\Persistence;

use Lucinda\Framework\Persistence\SecretBox;
use Lucinda\UnitTest\Result;

final class SecretBoxTest
{
    public function encrypt(): array
    {
        $key = str_repeat('k', SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
        $box = new SecretBox(base64_encode($key));
        $results = [];
        foreach (['', 'JBSWY3DPEHPK3PXP', "binary\0\xff"] as $secret) {
            $encoded = $box->encrypt($secret);
            $data = base64_decode($encoded, true);
            $results[] = new Result(
                $data !== false && strlen($data) === SODIUM_CRYPTO_SECRETBOX_NONCEBYTES
                    + SODIUM_CRYPTO_SECRETBOX_MACBYTES + strlen($secret),
                'ciphertext encodes a nonce and authenticated payload'
            );
            $nonce = substr($data, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
            $cipher = substr($data, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
            $results[] = new Result(
                sodium_crypto_secretbox_open($cipher, $nonce, $key) === $secret,
                'Sodium independently decrypts the exact plaintext bytes'
            );
        }
        $first = base64_decode($box->encrypt('same secret'), true);
        $second = base64_decode($box->encrypt('same secret'), true);
        $results[] = new Result(
            substr($first, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES)
                !== substr($second, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES),
            'repeated plaintext gets a fresh nonce'
        );
        return $results;
    }

    public function decrypt(): array
    {
        $key = str_repeat('k', SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
        $box = new SecretBox(base64_encode($key));
        $nonce = str_repeat('n', SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $results = [];
        foreach (['', 'JBSWY3DPEHPK3PXP', "binary\0\xff"] as $secret) {
            $encoded = base64_encode($nonce . sodium_crypto_secretbox($secret, $nonce, $key));
            $results[] = new Result($box->decrypt($encoded) === $secret, 'decrypts independently generated Sodium ciphertext');
        }
        $data = $nonce . sodium_crypto_secretbox('protected', $nonce, $key);
        $invalid = ['%%%', '', base64_encode(str_repeat('x', SODIUM_CRYPTO_SECRETBOX_NONCEBYTES + SODIUM_CRYPTO_SECRETBOX_MACBYTES - 1))];
        foreach ([0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, strlen($data) - 1] as $position) {
            $tampered = $data;
            $tampered[$position] = chr(ord($tampered[$position]) ^ 1);
            $invalid[] = base64_encode($tampered);
        }
        foreach ($invalid as $encoded) {
            $results[] = $this->rejectsCiphertext($box, $encoded);
        }
        $wrongKey = new SecretBox(base64_encode(str_repeat('w', SODIUM_CRYPTO_SECRETBOX_KEYBYTES)));
        $results[] = $this->rejectsCiphertext($wrongKey, base64_encode($data));
        return $results;
    }

    /** Checks explicit keys, environment fallback and restoration of process state. */
    public function keyConfiguration(): array
    {
        $previous = getenv('MFA_ENCRYPTION_KEY');
        $key = base64_encode(str_repeat('e', SODIUM_CRYPTO_SECRETBOX_KEYBYTES));
        $results = [];
        try {
            putenv('MFA_ENCRYPTION_KEY=' . $key);
            $box = new SecretBox();
            $results[] = new Result(
                (new SecretBox($key))->decrypt($box->encrypt('environment secret')) === 'environment secret',
                'omitted key loads the environment key'
            );
            foreach (['', '%%%', base64_encode(str_repeat('x', 31)), base64_encode(str_repeat('x', 33))] as $invalid) {
                $rejected = false;
                try {
                    new SecretBox($invalid);
                } catch (\RuntimeException) {
                    $rejected = true;
                }
                $results[] = new Result($rejected, 'invalid explicit key is rejected despite valid environment key');
            }
            foreach ([false, 'invalid'] as $environment) {
                putenv($environment === false ? 'MFA_ENCRYPTION_KEY' : 'MFA_ENCRYPTION_KEY=' . $environment);
                $rejected = false;
                try {
                    new SecretBox();
                } catch (\RuntimeException) {
                    $rejected = true;
                }
                $results[] = new Result($rejected, 'missing or malformed environment key is rejected');
                $explicit = new SecretBox($key);
                $results[] = new Result(
                    $explicit->decrypt($explicit->encrypt('explicit')) === 'explicit',
                    'valid explicit key overrides the environment'
                );
            }
        } finally {
            putenv($previous === false ? 'MFA_ENCRYPTION_KEY' : 'MFA_ENCRYPTION_KEY=' . $previous);
        }
        return $results;
    }

    private function rejectsCiphertext(SecretBox $box, string $encoded): Result
    {
        try {
            $box->decrypt($encoded);
        } catch (\UnexpectedValueException) {
            return new Result(true, 'malformed, tampered or wrong-key ciphertext is rejected');
        }
        return new Result(false, 'unauthenticated ciphertext must not return plaintext');
    }
}

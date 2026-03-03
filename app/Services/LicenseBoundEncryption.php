<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;

/**
 * Encrypt/decrypt payloads with a key derived from the license token.
 * Used for client-installed content so only a valid license can decrypt at runtime.
 * For BD widgets we use a public salt so the widget PHP can decrypt without the app key.
 */
class LicenseBoundEncryption
{
    /** Public salt used for client-side decrypt (must match the PHP we ship to BD). */
    public const CLIENT_SALT = 'tool-installer-license-v1';

    public function __construct(
        protected ?string $appKey = null
    ) {
        $this->appKey = $appKey ?? config('app.key');
    }

    /**
     * Derive a 32-byte key from the license token for AES-256.
     */
    protected function deriveKey(string $licenseToken, ?string $salt = null): string
    {
        $salt = $salt ?? $this->appKey;
        return hash_hmac('sha256', $licenseToken, $salt, true);
    }

    /**
     * Encrypt for client-side decrypt. Uses CLIENT_SALT so widget PHP can derive the same key.
     */
    public function encryptForClient(string $payload, string $licenseToken): string
    {
        $key = $this->deriveKey($licenseToken, self::CLIENT_SALT);
        $iv = random_bytes(16);
        $ciphertext = openssl_encrypt(
            $payload,
            'aes-256-cbc',
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );
        if ($ciphertext === false) {
            throw new \RuntimeException('License-bound encryption failed.');
        }
        return base64_encode($iv . $ciphertext);
    }

    /**
     * Encrypt a payload so only the holder of the given license token can decrypt.
     * Output is base64-encoded (iv + ciphertext).
     */
    public function encrypt(string $payload, string $licenseToken): string
    {
        $key = $this->deriveKey($licenseToken, $this->appKey);
        $iv = random_bytes(16);
        $ciphertext = openssl_encrypt(
            $payload,
            'aes-256-cbc',
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );
        if ($ciphertext === false) {
            throw new \RuntimeException('License-bound encryption failed.');
        }
        return base64_encode($iv . $ciphertext);
    }

    /**
     * Decrypt payload encrypted with encrypt() using the same license token.
     */
    public function decrypt(string $encrypted, string $licenseToken): string
    {
        $raw = base64_decode($encrypted, true);
        if ($raw === false || strlen($raw) < 17) {
            throw new \RuntimeException('Invalid license-bound encrypted payload.');
        }
        $key = $this->deriveKey($licenseToken);
        $iv = substr($raw, 0, 16);
        $ciphertext = substr($raw, 16);
        $payload = openssl_decrypt(
            $ciphertext,
            'aes-256-cbc',
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );
        if ($payload === false) {
            throw new \RuntimeException('License-bound decryption failed (wrong token or corrupted data).');
        }
        return $payload;
    }
}

<?php

namespace Promantus\Benepay\Model;

class Encryptor
{
    private const IV_LENGTH = 12; // 96 bits recommended for AES-GCM
    private const GCM_TAG_LENGTH = 16; // 128 bits / 8 = 16 bytes

    /**
     * Encrypt data using AES-GCM.
     *
     * @param string $plaintext  The data to encrypt.
     * @param string $hexKey     A hexadecimal-encoded encryption key.
     * @return string            URL-safe Base64 encoded encrypted payload (IV + ciphertext + tag).
     * @throws \RuntimeException On encryption or input failure.
     */
    public function encrypt(string $plaintext, string $hexKey): string
    {
        $key = $this->getKeyFromHex($hexKey);
        $iv = random_bytes(self::IV_LENGTH);
        $cipher = $this->getCipherMethod(strlen($key));

        $tag = '';
        $ciphertext = openssl_encrypt(
            $plaintext,
            $cipher,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            self::GCM_TAG_LENGTH
        );

        if ($ciphertext === false || empty($tag)) {
            throw new \RuntimeException('Encryption failed.');
        }

        $payload = $iv . $ciphertext . $tag;
        return $this->base64UrlEncode($payload);
    }

    /**
     * Decrypt AES-GCM encrypted data.
     *
     * @param string $encodedPayload  The URL-safe Base64 encoded payload (IV + ciphertext + tag).
     * @param string $hexKey          A hexadecimal-encoded encryption key.
     * @return string                 Decrypted plaintext.
     * @throws \RuntimeException      On decryption or input failure.
     */
    public function decrypt(string $encodedPayload, string $hexKey): string
    {
        $payload = $this->base64UrlDecode($encodedPayload);
        if ($payload === false || strlen($payload) < (self::IV_LENGTH + self::GCM_TAG_LENGTH)) {
            throw new \RuntimeException('Invalid encrypted payload.');
        }

        $key = $this->getKeyFromHex($hexKey);
        $cipher = $this->getCipherMethod(strlen($key));

        $iv = substr($payload, 0, self::IV_LENGTH);
        $tag = substr($payload, -self::GCM_TAG_LENGTH);
        $ciphertext = substr($payload, self::IV_LENGTH, -self::GCM_TAG_LENGTH);

        $plaintext = openssl_decrypt(
            $ciphertext,
            $cipher,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($plaintext === false) {
            throw new \RuntimeException('Decryption failed. Payload may be tampered or corrupted.');
        }

        return $plaintext;
    }

    /**
     * Convert hexadecimal key to binary and validate.
     *
     * @param string $hexKey
     * @return string
     * @throws \RuntimeException
     */
    private function getKeyFromHex(string $hexKey): string
    {
        $key = hex2bin($hexKey);
        if ($key === false) {
            throw new \RuntimeException('Invalid hexadecimal key.');
        }

        $length = strlen($key);
        if (!in_array($length, [16, 24, 32], true)) {
            throw new \RuntimeException("Invalid key length: {$length} bytes. Must be 16, 24, or 32.");
        }

        return $key;
    }

    /**
     * Return cipher method based on key length.
     *
     * @param int $keyLength
     * @return string
     */
    private function getCipherMethod(int $keyLength): string
    {
        return match ($keyLength) {
            16 => 'aes-128-gcm',
            24 => 'aes-192-gcm',
            32 => 'aes-256-gcm',
            default => throw new \RuntimeException('Unsupported key length.'),
        };
    }

    /**
     * Perform Base64 URL-safe encoding.
     *
     * @param string $data
     * @return string
     */
    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Perform Base64 URL-safe decoding.
     *
     * @param string $data
     * @return string|false
     */
    private function base64UrlDecode(string $data): string|false
    {
        $padding = 4 - (strlen($data) % 4);
        if ($padding < 4) {
            $data .= str_repeat('=', $padding);
        }
        return base64_decode(strtr($data, '-_', '+/'));
    }
}

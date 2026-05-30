<?php

namespace Lahiru\LaravelSolidGate\Support;

use Lahiru\LaravelSolidGate\Exceptions\SolidGateSignatureException;

/**
 * Signature validator for SolidGate webhook verification.
 */
class SignatureValidator
{
    /**
     * Generate a signature for the given payload.
     *
     * @param  string  $publicKey
     * @param  string  $payload
     * @param  string  $secretKey
     * @return string
     */
    public static function make(string $publicKey, string $payload, string $secretKey): string
    {
        $text = $publicKey . $payload . $publicKey;

        return base64_encode(hash_hmac('sha512', $text, $secretKey));
    }

    /**
     * Validate a received signature against the expected signature.
     *
     * @param  string  $publicKey
     * @param  string  $payload
     * @param  string  $secretKey
     * @param  string|null  $receivedSignature
     * @return bool
     */
    public static function isValid(
        string $publicKey,
        string $payload,
        string $secretKey,
        ?string $receivedSignature
    ): bool {
        if (empty($receivedSignature)) {
            return false;
        }

        $expected = self::make($publicKey, $payload, $secretKey);

        return hash_equals(trim($expected), trim($receivedSignature));
    }

    /**
     * Validate a received signature and throw an exception if invalid.
     *
     * @param  string  $publicKey
     * @param  string  $payload
     * @param  string  $secretKey
     * @param  string|null  $receivedSignature
     * @return bool
     * @throws SolidGateSignatureException
     */
    public static function validate(
        string $publicKey,
        string $payload,
        string $secretKey,
        ?string $receivedSignature
    ): bool {
        if (!self::isValid($publicKey, $payload, $secretKey, $receivedSignature)) {
            throw new SolidGateSignatureException('Invalid signature provided.');
        }

        return true;
    }
}
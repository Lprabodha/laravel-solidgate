<?php

namespace Lahiru\LaravelSolidGate\Support;

/**
 * Normalizes SolidGate API error payloads into human-readable strings.
 */
class ErrorMessageFormatter
{
    public static function fromResponse(array $responseData): string
    {
        if (isset($responseData['error']['messages'])) {
            return self::flatten($responseData['error']['messages']);
        }

        return $responseData['message']
            ?? $responseData['error']['message']
            ?? $responseData['error']['code']
            ?? (is_string($responseData['error'] ?? null) ? $responseData['error'] : null)
            ?? 'API request failed';
    }

    public static function flatten(mixed $messages): string
    {
        if (is_string($messages)) {
            return $messages;
        }

        if (! is_array($messages)) {
            return 'API request failed';
        }

        $parts = [];

        foreach ($messages as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $message) {
                    $parts[] = is_string($key) ? "{$key}: {$message}" : (string) $message;
                }

                continue;
            }

            $parts[] = is_string($key) ? "{$key}: {$value}" : (string) $value;
        }

        return implode(', ', $parts) ?: 'API request failed';
    }
}

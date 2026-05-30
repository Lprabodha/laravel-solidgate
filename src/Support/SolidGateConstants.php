<?php

namespace Lahiru\LaravelSolidGate\Support;

/**
 * SolidGate Constants
 *
 * Common constants used in SolidGate API operations.
 */
class SolidGateConstants
{
    /**
     * Zero decimal currencies that don't require conversion to cents.
     */
    public const ZERO_DECIMAL_CURRENCIES = [
        'bif',
        'clp',
        'djf',
        'gnf',
        'isk',
        'jpy',
        'kmf',
        'krw',
        'mga',
        'pyg',
        'rwf',
        'ugx',
        'vnd',
        'vuv',
        'xaf',
        'xof',
        'xpf',
    ];

    /**
     * Check if a currency is zero decimal.
     */
    public static function isZeroDecimalCurrency(string $currency): bool
    {
        return in_array(strtolower($currency), self::ZERO_DECIMAL_CURRENCIES);
    }

    /**
     * Convert amount to SolidGate format (cents for non-zero decimal currencies).
     */
    public static function formatAmount(float $amount, string $currency): int
    {
        if (self::isZeroDecimalCurrency($currency)) {
            return (int) $amount;
        }

        return (int) round($amount * 100);
    }

    /**
     * Convert amount from SolidGate format (cents to dollars for non-zero decimal currencies).
     */
    public static function parseAmount(int $amount, string $currency): float
    {
        if (self::isZeroDecimalCurrency($currency)) {
            return (float) $amount;
        }

        return (float) ($amount / 100);
    }
}

<?php

namespace Lahiru\LaravelSolidGate\Support;

/**
 * Cancel Subscription Reason Codes
 *
 * Official SolidGate cancellation reason codes.
 * These codes are used when canceling subscriptions via the API.
 *
 * @see https://docs.solidgate.com/ for more information
 */
class CancelSubscriptionReason
{
    /*
    |--------------------------------------------------------------------------
    | Fraud and Risk Codes
    |--------------------------------------------------------------------------
    |
    | Use these codes when cancellation is due to fraud or risk concerns.
    | Investigate the issue and mitigate risks.
    |
    */

    /**
     * 8.02 - Fraud chargeback received
     */
    public const FRAUD_CHARGEBACK_RECEIVED = '8.02';

    /**
     * 8.03 - Dispute received
     */
    public const DISPUTE_RECEIVED = '8.03';

    /**
     * 8.04 - Fraud alert received
     */
    public const FRAUD_ALERT_RECEIVED = '8.04';

    /**
     * 8.05 - Fraud decline received
     */
    public const FRAUD_DECLINE_RECEIVED = '8.05';

    /**
     * 8.07 - Recurring payment is blocked by antifraud
     */
    public const RECURRING_PAYMENT_BLOCKED_BY_ANTIFRAUD = '8.07';

    /**
     * 8.12 - Bank antifraud system
     */
    public const BANK_ANTIFRAUD_SYSTEM = '8.12';

    /*
    |--------------------------------------------------------------------------
    | Payment Method Codes
    |--------------------------------------------------------------------------
    |
    | Use these codes when cancellation is due to payment method issues.
    | Update the subscription token to restore recurring payments.
    |
    */

    /**
     * 8.01 - Card brand is not supported
     */
    public const CARD_BRAND_NOT_SUPPORTED = '8.01';

    /**
     * 8.10 - Card token has expired
     */
    public const CARD_TOKEN_EXPIRED = '8.10';

    /**
     * 8.11 - Token revoked by customer
     */
    public const TOKEN_REVOKED_BY_CUSTOMER = '8.11';

    /**
     * 8.15 - Recurring token is not found
     */
    public const RECURRING_TOKEN_NOT_FOUND = '8.15';

    /*
    |--------------------------------------------------------------------------
    | Customer-Initiated Codes
    |--------------------------------------------------------------------------
    |
    | Use these codes when cancellation is initiated by the customer or support.
    | Review cancellation reasons and trends affecting retention.
    |
    */

    /**
     * 8.06 - Cancellation by support
     */
    public const CANCELLATION_BY_SUPPORT = '8.06';

    /**
     * 8.14 - Cancellation by customer
     */
    public const CANCELLATION_BY_CUSTOMER = '8.14';

    /*
    |--------------------------------------------------------------------------
    | Subscription and Billing Codes
    |--------------------------------------------------------------------------
    |
    | Use these codes when cancellation is due to subscription or billing issues.
    |
    */

    /**
     * 8.08 - Subscription has expired
     */
    public const SUBSCRIPTION_EXPIRED = '8.08';

    /**
     * 8.09 - Cancellation after redemption period
     */
    public const CANCELLATION_AFTER_REDEMPTION_PERIOD = '8.09';

    /**
     * 8.13 - Invalid amount
     */
    public const INVALID_AMOUNT = '8.13';

    /*
    |--------------------------------------------------------------------------
    | Legacy/Backward Compatibility
    |--------------------------------------------------------------------------
    |
    | These constants are kept for backward compatibility.
    | Use the specific codes above instead.
    |
    */

    /**
     * @deprecated Use CANCELLATION_BY_SUPPORT (8.06) instead
     */
    public const CANCELLATION_BY_SUPPORT_LEGACY = 'CANCELLATION_BY_SUPPORT';

    /**
     * @deprecated Use CANCELLATION_BY_CUSTOMER (8.14) instead
     */
    public const CANCELLATION_BY_USER = 'CANCELLATION_BY_USER';

    /**
     * @deprecated Use INVALID_AMOUNT (8.13) instead
     */
    public const INVALID_AMOUNT_LEGACY = 'INVALID_AMOUNT';

    /**
     * Get all available cancel reason codes.
     *
     * @return array<string, string>
     */
    public static function all(): array
    {
        return [
            // Fraud and Risk
            'FRAUD_CHARGEBACK_RECEIVED' => self::FRAUD_CHARGEBACK_RECEIVED,
            'DISPUTE_RECEIVED' => self::DISPUTE_RECEIVED,
            'FRAUD_ALERT_RECEIVED' => self::FRAUD_ALERT_RECEIVED,
            'FRAUD_DECLINE_RECEIVED' => self::FRAUD_DECLINE_RECEIVED,
            'RECURRING_PAYMENT_BLOCKED_BY_ANTIFRAUD' => self::RECURRING_PAYMENT_BLOCKED_BY_ANTIFRAUD,
            'BANK_ANTIFRAUD_SYSTEM' => self::BANK_ANTIFRAUD_SYSTEM,

            // Payment Method
            'CARD_BRAND_NOT_SUPPORTED' => self::CARD_BRAND_NOT_SUPPORTED,
            'CARD_TOKEN_EXPIRED' => self::CARD_TOKEN_EXPIRED,
            'TOKEN_REVOKED_BY_CUSTOMER' => self::TOKEN_REVOKED_BY_CUSTOMER,
            'RECURRING_TOKEN_NOT_FOUND' => self::RECURRING_TOKEN_NOT_FOUND,

            // Customer-Initiated
            'CANCELLATION_BY_SUPPORT' => self::CANCELLATION_BY_SUPPORT,
            'CANCELLATION_BY_CUSTOMER' => self::CANCELLATION_BY_CUSTOMER,

            // Subscription and Billing
            'SUBSCRIPTION_EXPIRED' => self::SUBSCRIPTION_EXPIRED,
            'CANCELLATION_AFTER_REDEMPTION_PERIOD' => self::CANCELLATION_AFTER_REDEMPTION_PERIOD,
            'INVALID_AMOUNT' => self::INVALID_AMOUNT,
        ];
    }

    /**
     * Get cancel reason codes by category.
     *
     * @return array<string, array<string, string>>
     */
    public static function byCategory(): array
    {
        return [
            'fraud_and_risk' => [
                'FRAUD_CHARGEBACK_RECEIVED' => self::FRAUD_CHARGEBACK_RECEIVED,
                'DISPUTE_RECEIVED' => self::DISPUTE_RECEIVED,
                'FRAUD_ALERT_RECEIVED' => self::FRAUD_ALERT_RECEIVED,
                'FRAUD_DECLINE_RECEIVED' => self::FRAUD_DECLINE_RECEIVED,
                'RECURRING_PAYMENT_BLOCKED_BY_ANTIFRAUD' => self::RECURRING_PAYMENT_BLOCKED_BY_ANTIFRAUD,
                'BANK_ANTIFRAUD_SYSTEM' => self::BANK_ANTIFRAUD_SYSTEM,
            ],
            'payment_method' => [
                'CARD_BRAND_NOT_SUPPORTED' => self::CARD_BRAND_NOT_SUPPORTED,
                'CARD_TOKEN_EXPIRED' => self::CARD_TOKEN_EXPIRED,
                'TOKEN_REVOKED_BY_CUSTOMER' => self::TOKEN_REVOKED_BY_CUSTOMER,
                'RECURRING_TOKEN_NOT_FOUND' => self::RECURRING_TOKEN_NOT_FOUND,
            ],
            'customer_initiated' => [
                'CANCELLATION_BY_SUPPORT' => self::CANCELLATION_BY_SUPPORT,
                'CANCELLATION_BY_CUSTOMER' => self::CANCELLATION_BY_CUSTOMER,
            ],
            'subscription_and_billing' => [
                'SUBSCRIPTION_EXPIRED' => self::SUBSCRIPTION_EXPIRED,
                'CANCELLATION_AFTER_REDEMPTION_PERIOD' => self::CANCELLATION_AFTER_REDEMPTION_PERIOD,
                'INVALID_AMOUNT' => self::INVALID_AMOUNT,
            ],
        ];
    }

    /**
     * Check if a cancel reason code is valid.
     *
     * @param  string  $code
     * @return bool
     */
    public static function isValid(string $code): bool
    {
        return in_array($code, array_values(self::all()), true);
    }
}

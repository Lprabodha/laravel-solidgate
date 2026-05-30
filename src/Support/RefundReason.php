<?php

namespace Lahiru\LaravelSolidGate\Support;

/**
 * Refund Reason Codes
 *
 * Official SolidGate refund reason codes.
 * These codes are used when processing refunds via the API.
 *
 * @see https://docs.solidgate.com/payments/payments-insights/refund-reasons/ for more information
 */
class RefundReason
{
    /*
    |--------------------------------------------------------------------------
    | Customer-Initiated Refunds
    |--------------------------------------------------------------------------
    |
    | These codes apply to refunds initiated by customers or through manual
    | processes. Monitor the frequency to identify patterns and improve
    | customer satisfaction.
    |
    */

    /**
     * 0021 - Solidgate – request by user
     *
     * This code applies to any manual refunds initiated through the Solidgate Hub.
     * It indicates a refund made per the customer's request and does not
     * necessarily imply an issue.
     *
     * Recommendations: Monitor the frequency of these refunds to identify any
     * patterns or common reasons that may be addressed to improve customer satisfaction.
     */
    public const REQUEST_BY_USER = '0021';

    /*
    |--------------------------------------------------------------------------
    | Fraud and Risk-Related Refunds
    |--------------------------------------------------------------------------
    |
    | These codes are used when refunds are related to fraud detection,
    * risk management, or security concerns.
    |
    */

    /**
     * 0022 - Solidgate – issuer fraud notification
     *
     * This code is used when the issuer directly reports a fraudulent charge
     * to Solidgate. Support team may initiate a refund after contacting the merchant.
     * It signifies a flagged transaction due to fraudulent activity.
     *
     * Recommendations: Maintain close communication with Solidgate to understand
     * the nature of these fraudulent activities. Consider implementing additional
     * security measures if these incidents are frequent.
     */
    public const ISSUER_FRAUD_NOTIFICATION = '0022';

    /**
     * 0023 - Solidgate – risk department
     *
     * Result of fraud detection. The customer can ask for a refund for the transaction.
     * Indicates transactions flagged for potential fraud.
     *
     * Recommendations: Regularly review transactions flagged by the Risk Department
     * to understand if legitimate transactions are being affected.
     */
    public const RISK_DEPARTMENT = '0023';

    /**
     * 0027 - Solidgate – antifraud
     *
     * This code is used for automatic refund actions triggered by the antifraud system.
     * Indicates automated fraud detection measures.
     *
     * Recommendations: Consistently review and update your fraud prevention settings
     * to keep pace with emerging fraud patterns. Please contact the Solidgate team
     * to coordinate and improve these settings.
     */
    public const ANTIFRAUD = '0027';

    /**
     * 0025 - Solidgate – prevention alert
     *
     * Refunds are automatically issued when Solidgate receives a prevention alert.
     * Previously, the following refund codes 0016, 0017, 0018, 0019 were used.
     *
     * Recommendations: Work closely with Solidgate to understand the triggers for
     * these prevention alerts and consider tweaking your security settings or
     * customer verification processes to minimize them.
     */
    public const PREVENTION_ALERT = '0025';

    /*
    |--------------------------------------------------------------------------
    | Issuer and Scheme-Related Refunds
    |--------------------------------------------------------------------------
    |
    | These codes are used when refunds are related to issuer requests or
    * card scheme requirements.
    |
    */

    /**
     * 0024 - Solidgate – retrieval request
     *
     * When the issuer posts a retrieval request, the merchant may choose to
     * refund the transaction. Signifies a formal request by the issuer for
     * transaction documentation.
     *
     * Recommendations: If retrieval requests are frequent, review your transaction
     * descriptors and customer communication to reduce misunderstandings that
     * could lead to retrieval requests.
     */
    public const RETRIEVAL_REQUEST = '0024';

    /**
     * 0029 - Solidgate – reversed by schemes
     *
     * This rule applies when card schemes cannot confirm a payment authorization
     * during the clearing process. In such cases, to comply with card scheme requirements,
     * the Acquirer/PSP must reverse (void) the original authorization transaction to
     * release the blocked funds in the customer's account.
     *
     * Recommendations: Update the transaction status and ensure void requests are
     * triggered at the appropriate point to avoid mismatches. Track card order webhook
     * for further investigation.
     */
    public const REVERSED_BY_SCHEMES = '0029';

    /*
    |--------------------------------------------------------------------------
    | System and Technical Errors
    |--------------------------------------------------------------------------
    |
    | These codes are used when refunds are related to system errors or
    * technical issues.
    |
    */

    /**
     * 0026 - Solidgate – system error
     *
     * This code is used rarely, only in case of technical problems between Solidgate,
     * providers, and payment networks. Indicates a system malfunction or technical issue.
     *
     * Recommendations: Please contact the Solidgate team immediately to report any
     * system errors for troubleshooting. Review any commonalities in these errors
     * to preemptively address technical vulnerabilities.
     */
    public const SYSTEM_ERROR = '0026';

    /**
     * 0028 - Solidgate – expired authorization
     *
     * After the authorization expires, Solidgate may issue a void using this code.
     * Indicates an expired authorization for a transaction.
     *
     * Recommendations: Monitor the time between authorization and transaction completion
     * to minimize instances of expired authorizations. Please contact the Solidgate
     * team to coordinate and potentially extend authorization periods if necessary.
     */
    public const EXPIRED_AUTHORIZATION = '0028';

    /*
    |--------------------------------------------------------------------------
    | Legacy/Backward Compatibility
    |--------------------------------------------------------------------------
    |
    | These constants are kept for backward compatibility.
    * Use the specific codes above instead.
    |
    */

    /**
     * @deprecated Use REQUEST_BY_USER (0021) instead
     */
    public const REQUEST_BY_USER_LEGACY = 'REQUEST_BY_USER';

    /**
     * @deprecated Use ISSUER_FRAUD_NOTIFICATION (0022) or RISK_DEPARTMENT (0023) instead
     */
    public const FRAUDULENT = 'FRAUDULENT';

    /**
     * @deprecated Use SYSTEM_ERROR (0026) instead
     */
    public const PROCESSING_ERROR = 'PROCESSING_ERROR';

    /**
     * Get all available refund reason codes.
     *
     * @return array<string, string>
     */
    public static function all(): array
    {
        return [
            // Customer-Initiated
            'REQUEST_BY_USER' => self::REQUEST_BY_USER,

            // Fraud and Risk
            'ISSUER_FRAUD_NOTIFICATION' => self::ISSUER_FRAUD_NOTIFICATION,
            'RISK_DEPARTMENT' => self::RISK_DEPARTMENT,
            'ANTIFRAUD' => self::ANTIFRAUD,
            'PREVENTION_ALERT' => self::PREVENTION_ALERT,

            // Issuer and Scheme-Related
            'RETRIEVAL_REQUEST' => self::RETRIEVAL_REQUEST,
            'REVERSED_BY_SCHEMES' => self::REVERSED_BY_SCHEMES,

            // System and Technical Errors
            'SYSTEM_ERROR' => self::SYSTEM_ERROR,
            'EXPIRED_AUTHORIZATION' => self::EXPIRED_AUTHORIZATION,
        ];
    }

    /**
     * Get refund reason codes by category.
     *
     * @return array<string, array<string, string>>
     */
    public static function byCategory(): array
    {
        return [
            'customer_initiated' => [
                'REQUEST_BY_USER' => self::REQUEST_BY_USER,
            ],
            'fraud_and_risk' => [
                'ISSUER_FRAUD_NOTIFICATION' => self::ISSUER_FRAUD_NOTIFICATION,
                'RISK_DEPARTMENT' => self::RISK_DEPARTMENT,
                'ANTIFRAUD' => self::ANTIFRAUD,
                'PREVENTION_ALERT' => self::PREVENTION_ALERT,
            ],
            'issuer_and_scheme' => [
                'RETRIEVAL_REQUEST' => self::RETRIEVAL_REQUEST,
                'REVERSED_BY_SCHEMES' => self::REVERSED_BY_SCHEMES,
            ],
            'system_and_technical' => [
                'SYSTEM_ERROR' => self::SYSTEM_ERROR,
                'EXPIRED_AUTHORIZATION' => self::EXPIRED_AUTHORIZATION,
            ],
        ];
    }

    /**
     * Check if a refund reason code is valid.
     *
     * @param  string  $code
     * @return bool
     */
    public static function isValid(string $code): bool
    {
        return in_array($code, array_values(self::all()), true);
    }

    /**
     * Get description for a refund reason code.
     *
     * @param  string  $code
     * @return string|null
     */
    public static function getDescription(string $code): ?string
    {
        return match ($code) {
            self::REQUEST_BY_USER => 'Solidgate – request by user. Manual refunds initiated through the Solidgate Hub.',
            self::ISSUER_FRAUD_NOTIFICATION => 'Solidgate – issuer fraud notification. Issuer directly reports a fraudulent charge.',
            self::RISK_DEPARTMENT => 'Solidgate – risk department. Result of fraud detection.',
            self::RETRIEVAL_REQUEST => 'Solidgate – retrieval request. Issuer requests transaction documentation.',
            self::PREVENTION_ALERT => 'Solidgate – prevention alert. Automatic refunds when prevention alert is received.',
            self::SYSTEM_ERROR => 'Solidgate – system error. Technical problems between Solidgate, providers, and payment networks.',
            self::ANTIFRAUD => 'Solidgate – antifraud. Automatic refund actions triggered by the antifraud system.',
            self::EXPIRED_AUTHORIZATION => 'Solidgate – expired authorization. Authorization expired before transaction completion.',
            self::REVERSED_BY_SCHEMES => 'Solidgate – reversed by schemes. Card schemes cannot confirm payment authorization.',
            default => null,
        };
    }
}

<?php

namespace Lahiru\LaravelSolidGate\Support;

/**
 * Card payment type values for SolidGate charge/recurring/resign requests.
 *
 * @see https://api-docs.solidgate.com/#tag/Card-payments/operation/charge-card-order
 * @see https://docs.solidgate.com/payments/card-payments/card-payments-insights/payment-lifecycle/
 */
class PaymentType
{
    /** Customer-initiated transaction (CIT). */
    public const ONE_CLICK = '1-click';

    /** Subscription-based merchant-initiated transaction (MIT). */
    public const RECURRING = 'recurring';

    /** Reattempt of a merchant-initiated transaction (MIT). */
    public const RETRY = 'retry';

    /** Merchant-initiated debit for credits and installments (MIT). */
    public const INSTALLMENT = 'installment';

    /** Unscheduled withdrawal triggered by merchant conditions (MIT). */
    public const REBILL = 'rebill';

    /** Mail Order / Telephone Order — card-not-present (CIT). Do not send with card_cvv. */
    public const MOTO = 'moto';
}

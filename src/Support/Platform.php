<?php

namespace Lahiru\LaravelSolidGate\Support;

/**
 * Customer device platform values for SolidGate payment requests.
 *
 * @see https://api-docs.solidgate.com/#tag/Card-payments/operation/charge-card-order
 */
class Platform
{
    /** Desktop web browser. */
    public const WEB = 'WEB';

    /** Mobile web browser. */
    public const MOB = 'MOB';

    /** Native mobile or desktop application. */
    public const APP = 'APP';
}

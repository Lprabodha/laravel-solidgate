# Laravel SolidGate Package

[![Latest Version](https://img.shields.io/badge/version-1.0.1-blue.svg)](https://packagist.org/packages/lahiru/laravel-solidgate)
[![PHP Version](https://img.shields.io/badge/php-8.2%2B-blue.svg)](https://php.net)
[![Laravel Version](https://img.shields.io/badge/laravel-10.x%20%7C%2011.x%20%7C%2012.x-red.svg)](https://laravel.com)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

A Laravel package for integrating with the [SolidGate payment gateway API](https://api-docs.solidgate.com/). It wraps **74+ endpoints** for card payments, alternative payment methods, subscriptions, webhooks, reporting, and more — with correct request signing aligned to [Solidgate's official documentation](https://docs.solidgate.com/payments/integrate/access-to-api/).

## Features

- **74+ API endpoints** — card, APM, subscriptions, taxes, risks, checkout, webhooks, reporting
- **Correct HMAC-SHA512 signing** — matches Solidgate docs and the official PHP SDK
- **Webhook support** — signature + merchant header verification, Laravel events
- **Type-safe responses** — `SolidGateResponse` with dot-notation helpers
- **Custom exceptions** — `SolidGateApiException`, `SolidGateConfigurationException`, `SolidGateSignatureException`
- **Helper classes** — refund/cancel reason codes, zero-decimal currency utilities
- **Laravel HTTP client** — native `Http` facade integration
- **PHP 8.2+** — readonly properties, full type hints

## Requirements

- PHP 8.2+
- Laravel 10.x, 11.x, or 12.x

## Installation

```bash
composer require lahiru/laravel-solidgate
```

Publish the config file:

```bash
php artisan vendor:publish --tag=solidgate-config
```

## Configuration

Add your credentials to `.env`. Get keys from **Solidgate Hub → Developers → Channel details**:

- API keys: `api_pk_*` / `api_sk_*`
- Webhook keys: `wh_pk_*` / `wh_sk_*`

```env
# Required
SOLIDGATE_PUBLIC_KEY=api_pk_your_public_key
SOLIDGATE_SECRET_KEY=api_sk_your_secret_key

# API base URLs (defaults shown)
SOLIDGATE_API_BASE_URL=https://pay.solidgate.com/api/v1/
SOLIDGATE_SUBSCRIPTIONS_BASE_URL=https://subscriptions.solidgate.com/api/v1/
SOLIDGATE_GATE_BASE_URL=https://gate.solidgate.com/api/
SOLIDGATE_REPORTS_BASE_URL=https://reports.solidgate.com/
SOLIDGATE_TIMEOUT=30
SOLIDGATE_VERIFY_SSL=true
SOLIDGATE_LOG_REQUESTS=false

# Webhooks (optional)
SOLIDGATE_WEBHOOK_ENABLED=false
SOLIDGATE_WEBHOOK_PATH=solidgate/webhook
SOLIDGATE_WEBHOOK_PUBLIC_KEY=wh_pk_your_webhook_public_key
SOLIDGATE_WEBHOOK_SECRET=wh_sk_your_webhook_secret
SOLIDGATE_SIGNATURE_HEADER=Signature
SOLIDGATE_WEBHOOK_MIDDLEWARE=solidgate.webhook
```

> **Important:** Use the correct base URL for each API group. Subscription endpoints must use `subscriptions.solidgate.com`, not `pay.solidgate.com`.

## Quick Start

### Facade

```php
use Lahiru\LaravelSolidGate\Facades\SolidGate;

$response = SolidGate::charge([
    'amount' => 10000,
    'currency' => 'USD',
    'order_id' => 'order-123',
    'customer_email' => 'customer@example.com',
]);

if ($response->isSuccessful()) {
    $status = $response->get('order.status');
}
```

### Dependency injection

```php
use Lahiru\LaravelSolidGate\Contracts\SolidGateClientInterface;

class PaymentController
{
    public function __construct(
        protected SolidGateClientInterface $solidgate
    ) {}

    public function charge(Request $request)
    {
        return $this->solidgate->charge($request->only([
            'amount', 'currency', 'order_id', 'customer_email',
        ]));
    }
}
```

## Card Payments

```php
// Charge
SolidGate::charge([...]);

// Google Pay / Apple Pay
SolidGate::chargeWithGooglePay([...]);
SolidGate::chargeWithApplePay([...]);

// Recurring, resign, incremental auth
SolidGate::recurring([...]);
SolidGate::resignTransaction([...]);
SolidGate::createIncrementalAuth([
    'order_id' => 'order-123',
    'amount' => 500,
]);

// Order status, refund, void, settle
SolidGate::status(['order_id' => 'order-123']);
SolidGate::getOrderStatus('order-123');
SolidGate::refund(['order_id' => 'order-123', 'amount' => 5000]);
SolidGate::void(['order_id' => 'order-123']);
SolidGate::settle(['order_id' => 'order-123', 'amount' => 10000]);

// ARN codes
SolidGate::getArnCodes(['order_id' => 'order-123']);
```

## Alternative Payment Methods

```php
// Initiate APM payment (PayPal, Pix, etc.)
SolidGate::initializeAlternativePayment([
    'payment_method' => 'paypal-vault',
    'order_id' => 'order-123',
    'amount' => 1020,
    'currency' => 'USD',
]);

// APM order status & token revoke
SolidGate::getAlternativePaymentOrderStatus('order-123');
SolidGate::revokeRecurringToken(['token' => 'token-value']);

// APM refund (uses gate API)
SolidGate::processFullRefund('order-123', 1000, 'paypal');
```

## Subscriptions

```php
use Lahiru\LaravelSolidGate\Support\CancelSubscriptionReason;

SolidGate::retrieveSubscription($subscriptionId);
SolidGate::getSubscriptionList($customerId);
SolidGate::updateSubscription($subscriptionId, ['product_id' => $newProductId]);
SolidGate::switchSubscriptionProduct($subscriptionId, $newProductId);
SolidGate::updatePaymentMethodToken($subscriptionId, $token);

SolidGate::cancelSubscription(
    $subscriptionId,
    CancelSubscriptionReason::CANCELLATION_BY_CUSTOMER
);

SolidGate::cancelSubscriptionsByCustomer(
    $customerId,
    CancelSubscriptionReason::CANCELLATION_BY_CUSTOMER
);

SolidGate::restoreSubscription($subscriptionId, $expireDate);
SolidGate::createSubscriptionPause($subscriptionId, '2026-12-31', '2026-06-01');
SolidGate::updateSubscriptionPause($subscriptionId, [...]);
SolidGate::removeSubscriptionPause($subscriptionId);

SolidGate::listInvoicesBySubscription($subscriptionId);
SolidGate::listOrdersByInvoice($invoiceId);
```

## Products & Prices

```php
SolidGate::createProduct([...]);
SolidGate::getProductList(['filter' => ['status' => 'active']]);
SolidGate::getProduct($productId);
SolidGate::updateProduct($productId, [...]);
SolidGate::archiveProduct($productId);

SolidGate::createProductPrice($productId, [...]);
SolidGate::getProductPrices($productId, $limit = 80, $offset = 0);
SolidGate::updateProductPrice($productId, $priceId, [...]);
SolidGate::retrieveProductPrices(['product_id' => $productId]);
SolidGate::calculateProductPrice(['product_id' => $productId, 'currency' => 'USD']);
```

## Taxes

Tax reports are generated asynchronously. First create a report, then download it by `report_id`:

```php
// Step 1: Request report generation
$response = SolidGate::createTransactionalTax([
    'date_from' => '2025-01-15 11:00:00',
    'date_to' => '2025-06-20 13:00:00',
    'environment' => 'all',
]);

$response = SolidGate::createSummaryTax([
    'date_from' => '2025-01-15 11:00:00',
    'date_to' => '2025-06-20 13:00:00',
]);

// Step 2: Download by report ID from the response
SolidGate::downloadTransactionalTax('TAX_250702_140728_CHECKOUT');
SolidGate::downloadSummaryTax('TAX_250702_140728_CHECKOUT');
```

## Refunds with Reason Codes

```php
use Lahiru\LaravelSolidGate\Support\RefundReason;

SolidGate::processFullRefund(
    orderId: 'order-123',
    amount: 5000,
    method: 'card',
    refundReasonCode: RefundReason::REQUEST_BY_USER
);

SolidGate::processPartialRefund('order-123', 2500, 'card', RefundReason::REQUEST_BY_USER);
```

See [Refund Reasons](https://docs.solidgate.com/payments/payments-insights/refund-reasons/) and `RefundReason` / `CancelSubscriptionReason` helper classes for all official codes.

## Checkout Solutions

```php
// Hosted payment page
$response = SolidGate::createPaymentPage([
    'order' => [...],
    'page_customization' => [...],
]);
$pageUrl = $response->get('url');
SolidGate::deactivatePaymentPage($pageId);

// Payment link
$response = SolidGate::createPaymentLink([
    'order' => [...],
    'page_customization' => [...],
    'configuration' => ['usage_mode' => 'reusable'],
]);
SolidGate::deactivatePaymentLink($linkId);
```

## Webhooks

Enable in `.env`:

```env
SOLIDGATE_WEBHOOK_ENABLED=true
SOLIDGATE_WEBHOOK_PATH=solidgate/webhook
```

The package registers `POST /solidgate/webhook` with signature verification middleware.

Listen for events:

```php
use Lahiru\LaravelSolidGate\Events\SolidGateWebhookReceived;

// EventServiceProvider
protected $listen = [
    SolidGateWebhookReceived::class => [
        ProcessSolidGateWebhook::class,
    ],
];
```

Manual signature verification:

```php
use Lahiru\LaravelSolidGate\Support\SignatureValidator;

SignatureValidator::validate($publicKey, $request->getContent(), $secretKey, $signature);
```

## Reporting

Reports are generated via POST, then downloaded by `report_id`:

```php
// Generate reports
SolidGate::getCardOrdersReport([
    'date_from' => '2025-08-15 11:00:00',
    'date_to' => '2025-08-18 11:00:00',
]);

SolidGate::getApmOrdersReport([...]);
SolidGate::getSubscriptionsReport([...]);
SolidGate::getChargebacksReport([...]);
SolidGate::getPaypalDisputesReport([...]);
SolidGate::getPreventionAlertsReport([...]);
SolidGate::getCardFraudAlertsReport([...]);
SolidGate::getFinancialEntriesByDateRange([...]);

// Download generated reports
SolidGate::downloadPreventionAlerts($reportId);
SolidGate::downloadFinancialEntries($reportId);
```

## Risks, Webhooks API & Files

```php
// Fraud prevention
SolidGate::createFraudPreventionListItems(['items' => [...]]);
SolidGate::listFraudPreventionListItems([...]);
SolidGate::deleteFraudPreventionListItem($itemId);

// Dispute representment
SolidGate::createDisputeRepresentment([...]);
SolidGate::enrichDisputeRepresentment([...]);

// Webhook endpoint management
SolidGate::createWebhookEndpoint([...]);
SolidGate::listWebhookEndpoints();
SolidGate::updateWebhookEndpoint($webhookId, [...]);
SolidGate::deleteWebhookEndpoint($webhookId);

// File upload URL
SolidGate::createFile([
    'file_name' => 'document.pdf',
    'file_type' => 'application/pdf',
    'file_size' => 1024000,
]);
```

## Direct API Access

For endpoints not wrapped by a helper method:

```php
// Pay API (card payments)
SolidGate::send('charge', $attributes);

// Subscriptions API
SolidGate::subscriptions('subscription/status', ['subscription_id' => $id]);

// Gate API (APM recurring, status, etc.)
SolidGate::gate('v1/recurring', $attributes);
SolidGate::gate('v1/status', ['order_id' => $orderId]);
```

## Working with Responses

```php
$response = SolidGate::charge([...]);

$response->isSuccessful();       // HTTP 2xx
$response->get('order.status');  // Dot notation
$response->toArray();
$response->toJson();
$response->statusCode;
```

## Error Handling

```php
use Lahiru\LaravelSolidGate\Exceptions\SolidGateApiException;
use Lahiru\LaravelSolidGate\Exceptions\SolidGateConfigurationException;

try {
    $response = SolidGate::charge([...]);
} catch (SolidGateApiException $e) {
    logger()->error('SolidGate API error', [
        'message' => $e->getMessage(),
        'response' => $e->getResponse(),
        'code' => $e->getCode(),
    ]);
} catch (SolidGateConfigurationException $e) {
    logger()->error('SolidGate not configured', ['message' => $e->getMessage()]);
}
```

## Currency Helpers

```php
use Lahiru\LaravelSolidGate\Support\SolidGateConstants;

SolidGateConstants::isZeroDecimalCurrency('JPY');      // true
SolidGateConstants::formatAmount(99.99, 'USD');        // 9999
SolidGateConstants::parseAmount(9999, 'USD');          // 99.99
```

## Development

Clone the repository and install dependencies:

```bash
git clone https://github.com/lahiru/laravel-solidgate.git
cd laravel-solidgate
composer install
cp .env.example .env   # if available — never commit real keys
```

Run tests:

```bash
composer test
# or
php vendor/bin/phpunit
```

Run static analysis / formatting:

```bash
composer analyse
composer format
```

## API Coverage

| Category | Endpoints |
|----------|-----------|
| Card payments | charge, google-pay, apple-pay, increment, recurring, resign, refund, void, settle, status, arn-code |
| Alternative payments | init-payment, v1/recurring, v1/recurring-token/cancel, v1/refund, v1/status |
| Products & prices | CRUD, calculate, retrieve |
| Taxes | transactional, summary + download |
| Subscriptions | Full lifecycle, pause, invoices, orders |
| Risks | Fraud prevention, dispute representment |
| Checkout | Payment page & link (init/deactivate) |
| Webhooks | Endpoint CRUD |
| Reporting | Card, APM, subscriptions, chargebacks, disputes, alerts, financial entries |
| Files | get-upload-url |

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

### 1.0.1

- Fixed HMAC signature algorithm to match Solidgate official docs
- Fixed request body signing (send exact JSON bytes)
- Fixed GET request signatures (empty body)
- Corrected 30+ API endpoint paths
- Added unit tests, `phpunit.xml`, and `LICENSE`
- Improved webhook merchant header validation

## License

MIT — see [LICENSE](LICENSE).

## References

- [SolidGate API Reference](https://api-docs.solidgate.com/)
- [Access to API (signing)](https://docs.solidgate.com/payments/integrate/access-to-api/)
- [SolidGate Payment Guide](https://docs.solidgate.com/)
- [Refund Reasons](https://docs.solidgate.com/payments/payments-insights/refund-reasons/)
- [Subscription Cancel Codes](https://docs.solidgate.com/billing/subscription-overview/subscription-insights/subscription-cancel-codes/)

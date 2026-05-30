# Laravel SolidGate

[![Latest Version](https://img.shields.io/badge/version-1.0.2-blue.svg)](https://packagist.org/packages/lahiru/laravel-solidgate)
[![PHP Version](https://img.shields.io/badge/php-8.2%2B-blue.svg)](https://php.net)
[![Laravel Version](https://img.shields.io/badge/laravel-10.x%20%7C%2011.x%20%7C%2012.x-red.svg)](https://laravel.com)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

A Laravel package for the [SolidGate payment gateway](https://api-docs.solidgate.com/). It wraps **74+ endpoints** for card payments, alternative payment methods, subscriptions, webhooks, and reporting — with HMAC-SHA512 signing aligned to the [official SolidGate docs](https://docs.solidgate.com/payments/integrate/access-to-api/).

---

## Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Quick Start](#quick-start)
- [Card Payments](#card-payments)
- [Alternative Payments (APM)](#alternative-payments-apm)
- [Subscriptions](#subscriptions)
- [Products, Taxes & Reporting](#products-taxes--reporting)
- [Webhooks](#webhooks)
- [Responses & Error Handling](#responses--error-handling)
- [Helper Classes](#helper-classes)
- [Troubleshooting](#troubleshooting)
- [API Reference Map](#api-reference-map)
- [Development](#development)
- [Changelog](#changelog)
- [License & References](#license--references)

---

## Features

| Feature | Description |
|---------|-------------|
| **74+ endpoints** | Card, APM, subscriptions, taxes, checkout, webhooks, reporting |
| **Correct signing** | HMAC-SHA512 over exact JSON body — matches official PHP SDK |
| **Smart defaults** | Auto-fills `platform`, `payment_type`, and card expiry formatting |
| **Type-safe responses** | `SolidGateResponse` with dot-notation and error helpers |
| **Webhook support** | Signature verification middleware + Laravel events |
| **Helper classes** | `Platform`, `PaymentType`, `RefundReason`, currency utilities |
| **Laravel native** | HTTP client, service container, facade, config publishing |

---

## Requirements

- PHP 8.2+
- Laravel 10.x, 11.x, or 12.x

---

## Installation

```bash
composer require lahiru/laravel-solidgate
```

Publish the config (recommended):

```bash
php artisan vendor:publish --tag=solidgate-config
```

Copy environment variables from `.env.example`:

```bash
cp vendor/lahiru/laravel-solidgate/.env.example .env.example.solidgate
# Merge the SolidGate keys into your .env
```

---

## Configuration

Get your keys from **SolidGate Hub → Developers → Channel details**:

| Key type | Prefix | Used for |
|----------|--------|----------|
| API keys | `api_pk_*` / `api_sk_*` | Outgoing API requests |
| Webhook keys | `wh_pk_*` / `wh_sk_*` | Incoming webhook verification |

### Environment variables

```env
# ── Required ──────────────────────────────────────────────
SOLIDGATE_PUBLIC_KEY=api_pk_your_public_key
SOLIDGATE_SECRET_KEY=api_sk_your_secret_key

# ── API URLs (defaults are production) ──────────────────
SOLIDGATE_API_BASE_URL=https://pay.solidgate.com/api/v1/
SOLIDGATE_SUBSCRIPTIONS_BASE_URL=https://subscriptions.solidgate.com/api/v1/
SOLIDGATE_GATE_BASE_URL=https://gate.solidgate.com/api/
SOLIDGATE_REPORTS_BASE_URL=https://reports.solidgate.com/
SOLIDGATE_TIMEOUT=30
SOLIDGATE_VERIFY_SSL=true
SOLIDGATE_LOG_REQUESTS=false

# ── Payment defaults (applied when fields are omitted) ────
SOLIDGATE_DEFAULT_PLATFORM=WEB
SOLIDGATE_DEFAULT_PAYMENT_TYPE=1-click

# ── Webhooks (optional) ───────────────────────────────────
SOLIDGATE_WEBHOOK_ENABLED=false
SOLIDGATE_WEBHOOK_PATH=solidgate/webhook
SOLIDGATE_WEBHOOK_PUBLIC_KEY=wh_pk_your_webhook_public_key
SOLIDGATE_WEBHOOK_SECRET=wh_sk_your_webhook_secret
SOLIDGATE_SIGNATURE_HEADER=Signature
SOLIDGATE_WEBHOOK_MIDDLEWARE=solidgate.webhook
```

> **Tip:** Each API group uses a different base URL. Card payments go to `pay.solidgate.com`, subscriptions to `subscriptions.solidgate.com`, APM to `gate.solidgate.com`, and reports to `reports.solidgate.com`. The package routes these automatically — you only call the facade methods.

---

## Quick Start

### 1. Make your first charge

```php
use Lahiru\LaravelSolidGate\Facades\SolidGate;
use Lahiru\LaravelSolidGate\Support\Platform;

$response = SolidGate::charge([
    'amount'            => 10000,          // $100.00 in cents
    'currency'          => 'USD',
    'order_id'          => (string) Str::uuid(),
    'order_description' => 'Premium package',
    'customer_email'    => 'customer@example.com',
    'ip_address'        => $request->ip(),
    'platform'          => Platform::WEB,  // WEB | MOB | APP
    'card_number'       => '4111111111111111',
    'card_holder'       => 'John Doe',
    'card_exp_month'    => '12',
    'card_exp_year'     => 2030,
    'card_cvv'          => '123',
]);

if ($response->isSuccessful()) {
    $status = $response->get('order.status'); // e.g. "processing", "settle_ok"
} else {
    $error = $response->getErrorMessage();
}
```

`platform` and `payment_type` are auto-filled from config when omitted. Card expiry fields are normalized automatically (`3` → `"03"`, year cast to integer).

### 2. Full controller example

```php
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Lahiru\LaravelSolidGate\Contracts\SolidGateClientInterface;
use Lahiru\LaravelSolidGate\Support\Platform;

class PaymentController extends Controller
{
    public function __construct(
        protected SolidGateClientInterface $solidgate
    ) {}

    public function charge(Request $request): JsonResponse
    {
        $response = $this->solidgate->charge([
            'amount'            => $request->integer('amount'),
            'currency'          => $request->string('currency'),
            'order_id'          => (string) Str::uuid(),
            'order_description' => $request->string('order_description'),
            'customer_email'    => $request->string('customer_email'),
            'ip_address'        => $request->ip(),
            'platform'          => Platform::WEB,
            'card_number'       => $request->string('card_number'),
            'card_holder'       => $request->string('card_holder'),
            'card_exp_month'    => $request->string('card_exp_month'),
            'card_exp_year'     => $request->integer('card_exp_year'),
            'card_cvv'          => $request->string('card_cvv'),
        ]);

        if (! $response->isSuccessful()) {
            return response()->json([
                'error'   => $response->getError(),
                'message' => $response->getErrorMessage(),
            ], 422);
        }

        return response()->json([
            'status' => $response->get('order.status'),
            'order'  => $response->get('order'),
        ]);
    }
}
```

### 3. Recurring / token payment

```php
use Lahiru\LaravelSolidGate\Support\PaymentType;

$response = SolidGate::recurring([
    'amount'          => 10000,
    'currency'        => 'USD',
    'order_id'        => (string) Str::uuid(),
    'customer_email'  => 'customer@example.com',
    'ip_address'      => $request->ip(),
    'payment_type'    => PaymentType::RECURRING,
    'recurring_token' => 'token-from-previous-payment',
]);
```

---

## Card Payments

All card endpoints use the **pay API** (`pay.solidgate.com/api/v1/`).

### Required fields for `charge()`

| Field | Type | Example | Notes |
|-------|------|---------|-------|
| `amount` | integer | `10000` | Minor units (cents for USD) |
| `currency` | string | `"USD"` | ISO 4217 |
| `order_id` | string | UUID | Must be unique per attempt |
| `order_description` | string | `"Premium plan"` | |
| `customer_email` | string | `"user@mail.com"` | |
| `ip_address` | string | `"203.0.113.0"` | Public IP only — see [Troubleshooting](#troubleshooting) |
| `platform` | string | `"WEB"` | `WEB`, `MOB`, or `APP` |
| `card_number` | string | `"4111…"` | Full PAN, 12–19 digits |
| `card_holder` | string | `"John Doe"` | Name on card |
| `card_exp_month` | string | `"12"` | Zero-padded automatically |
| `card_exp_year` | integer | `2030` | 4-digit year |
| `card_cvv` | string | `"123"` | Required for first payment |

For token payments, omit card fields and pass `payment_type` + `recurring_token` instead.

### Available methods

```php
// ── Process payments ──────────────────────────────────────
SolidGate::charge([...]);              // Charge card
SolidGate::auth([...]);                // Authorize only (no capture)
SolidGate::recurring([...]);           // Token-based charge
SolidGate::resignTransaction([...]);   // Re-sign expired token
SolidGate::chargeWithGooglePay([...]);
SolidGate::chargeWithApplePay([...]);
SolidGate::createIncrementalAuth([
    'order_id' => 'order-123',
    'amount'   => 500,
]);

// ── Post-payment operations ───────────────────────────────
SolidGate::status(['order_id' => 'order-123']);
SolidGate::getOrderStatus('order-123');
SolidGate::refund(['order_id' => 'order-123', 'amount' => 5000]);
SolidGate::void(['order_id' => 'order-123']);
SolidGate::settle(['order_id' => 'order-123', 'amount' => 10000]);
SolidGate::getArnCodes(['order_id' => 'order-123']);

// ── Refunds with reason codes ─────────────────────────────
use Lahiru\LaravelSolidGate\Support\RefundReason;

SolidGate::processFullRefund('order-123', 5000, 'card', RefundReason::REQUEST_BY_USER);
SolidGate::processPartialRefund('order-123', 2500, 'card', RefundReason::REQUEST_BY_USER);
```

---

## Alternative Payments (APM)

APM endpoints use the **gate API** (`gate.solidgate.com/api/`), not the pay API.

```php
use Lahiru\LaravelSolidGate\Support\Platform;

// Start payment (PayPal, Pix, etc.)
$response = SolidGate::initializeAlternativePayment([
    'payment_method'    => 'paypal-vault',
    'order_id'          => 'order-123',
    'amount'            => 1020,
    'currency'          => 'USD',
    'customer_email'    => 'customer@example.com',
    'order_description' => 'Premium package',
    'ip_address'        => $request->ip(),
    'platform'          => Platform::WEB,
]);

// Token-based APM recurring
SolidGate::recurringAlternativePayment([
    'order_id'       => 'order-123',
    'amount'         => 1020,
    'currency'       => 'USD',
    'payment_method' => 'paypal-vault',
    'token'          => 'token-from-previous-payment',
]);

// Status, revoke, refund
SolidGate::getAlternativePaymentOrderStatus('order-123');
SolidGate::revokeRecurringToken(['token' => 'token-value']);
SolidGate::processFullRefund('order-123', 1000, 'paypal');
```

> **Note:** Card recurring uses `recurring_token`. APM recurring uses `token`. These are different field names per SolidGate API spec.

---

## Subscriptions

All subscription endpoints use `subscriptions.solidgate.com/api/v1/`.

```php
use Lahiru\LaravelSolidGate\Support\CancelSubscriptionReason;

// Read & update
SolidGate::retrieveSubscription($subscriptionId);
SolidGate::getSubscriptionList($customerId);
SolidGate::updateSubscription($subscriptionId, ['product_id' => $newProductId]);
SolidGate::switchSubscriptionProduct($subscriptionId, $newProductId);
SolidGate::updatePaymentMethodToken($subscriptionId, $token);

// Cancel & restore
SolidGate::cancelSubscription($subscriptionId, CancelSubscriptionReason::CANCELLATION_BY_CUSTOMER);
SolidGate::cancelSubscriptionsByCustomer($customerId, CancelSubscriptionReason::CANCELLATION_BY_CUSTOMER);
SolidGate::restoreSubscription($subscriptionId, $expireDate);

// Pause schedule
SolidGate::createSubscriptionPause($subscriptionId, '2026-12-31', '2026-06-01');
SolidGate::updateSubscriptionPause($subscriptionId, [...]);
SolidGate::removeSubscriptionPause($subscriptionId);

// Invoices & orders
SolidGate::listInvoicesBySubscription($subscriptionId);
SolidGate::listOrdersByInvoice($invoiceId);
```

---

## Products, Taxes & Reporting

```php
// Products & prices
SolidGate::createProduct([...]);
SolidGate::getProductList(['filter' => ['status' => 'active']]);
SolidGate::getProduct($productId);
SolidGate::updateProduct($productId, [...]);
SolidGate::archiveProduct($productId);
SolidGate::createProductPrice($productId, [...]);
SolidGate::getProductPrices($productId);
SolidGate::calculateProductPrice(['product_id' => $productId, 'currency' => 'USD']);

// Taxes (async: create report, then download by report_id)
$response = SolidGate::createTransactionalTax([
    'date_from' => '2025-01-15 11:00:00',
    'date_to'   => '2025-06-20 13:00:00',
]);
SolidGate::downloadTransactionalTax('TAX_250702_140728_CHECKOUT');

// Reports (async: generate, then download)
SolidGate::getCardOrdersReport(['date_from' => '...', 'date_to' => '...']);
SolidGate::getApmOrdersReport([...]);
SolidGate::getSubscriptionsReport([...]);
SolidGate::getChargebacksReport([...]);
SolidGate::downloadFinancialEntries($reportId);
SolidGate::getRoutingEventsReport([...]);
SolidGate::downloadRoutingEvents($reportId);

// Checkout (hosted page & payment link)
$response = SolidGate::createPaymentPage(['order' => [...], 'page_customization' => [...]]);
$pageUrl = $response->get('url');
SolidGate::deactivatePaymentPage($pageId);
SolidGate::createPaymentLink([...]);
SolidGate::deactivatePaymentLink($linkId);

// Risks & files
SolidGate::createFraudPreventionListItems(['items' => [...]]);
SolidGate::createDisputeRepresentment([...]);
SolidGate::createFile(['file_name' => 'document.pdf', 'file_type' => 'application/pdf', 'file_size' => 1024000]);
```

---

## Webhooks

### Setup

```env
SOLIDGATE_WEBHOOK_ENABLED=true
SOLIDGATE_WEBHOOK_PATH=solidgate/webhook
SOLIDGATE_WEBHOOK_PUBLIC_KEY=wh_pk_your_webhook_public_key
SOLIDGATE_WEBHOOK_SECRET=wh_sk_your_webhook_secret
```

The package registers `POST /solidgate/webhook` with signature verification middleware.

**Exclude from CSRF** (Laravel 11+):

```php
// bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    $middleware->validateCsrfTokens(except: [
        'solidgate/webhook',
    ]);
})
```

### Incoming headers

| Header | Description |
|--------|-------------|
| `solidgate-event-type` | Event name, e.g. `card_gate.order.updated` |
| `solidgate-event-id` | Unique event ID |
| `Signature` | HMAC signature of raw request body |
| `Merchant` | Webhook public key |

### Handle events

```php
use Lahiru\LaravelSolidGate\Events\SolidGateWebhookReceived;

// app/Providers/EventServiceProvider.php (or AppServiceProvider)
protected $listen = [
    SolidGateWebhookReceived::class => [
        ProcessSolidGateWebhook::class,
    ],
];
```

```php
// app/Listeners/ProcessSolidGateWebhook.php
public function handle(SolidGateWebhookReceived $event): void
{
    match ($event->eventType) {
        'card_gate.order.updated' => $this->handleOrderUpdated($event->payload),
        'card_gate.chargeback.received' => $this->handleChargeback($event->payload),
        default => null,
    };
}
```

### Manual signature verification

```php
use Lahiru\LaravelSolidGate\Support\SignatureValidator;

SignatureValidator::validate(
    $publicKey,
    $request->getContent(),
    $secretKey,
    $request->header('Signature')
);
```

---

## Responses & Error Handling

SolidGate has **two types of errors**. Handle both:

| Type | HTTP status | How to detect |
|------|-------------|---------------|
| Validation / business error | `200` with `error` object | `! $response->isSuccessful()` |
| Transport / server error | `4xx` / `5xx` | `SolidGateApiException` thrown |

```php
use Lahiru\LaravelSolidGate\Exceptions\SolidGateApiException;
use Lahiru\LaravelSolidGate\Exceptions\SolidGateConfigurationException;

try {
    $response = SolidGate::charge([...]);

    if (! $response->isSuccessful()) {
        // HTTP 200 but SolidGate returned an error object
        return response()->json([
            'error'   => $response->getError(),       // ['code' => '2.01', 'messages' => [...]]
            'message' => $response->getErrorMessage(), // "platform: Platform is empty or invalid"
        ], 422);
    }

    // Success
    $order = $response->get('order');
    $status = $response->get('order.status');

} catch (SolidGateApiException $e) {
    // HTTP 4xx/5xx or connection timeout
    logger()->error('SolidGate API error', [
        'message'  => $e->getMessage(),
        'response' => $e->getResponse(),
        'code'     => $e->getCode(),
    ]);
} catch (SolidGateConfigurationException $e) {
    logger()->error('SolidGate not configured', ['message' => $e->getMessage()]);
}
```

### Response helpers

```php
$response->isSuccessful();        // true only when HTTP 2xx AND no error object
$response->hasError();           // true when error object is present
$response->getError();           // raw error array
$response->getErrorMessage();    // flattened human-readable string
$response->get('order.status');  // dot-notation access
$response->toArray();
$response->toJson();
$response->statusCode;
```

---

## Helper Classes

### `Platform` — customer device

```php
use Lahiru\LaravelSolidGate\Support\Platform;

Platform::WEB;  // Desktop browser
Platform::MOB;  // Mobile browser
Platform::APP;  // Native app
```

### `PaymentType` — CIT / MIT classification

```php
use Lahiru\LaravelSolidGate\Support\PaymentType;

PaymentType::ONE_CLICK;    // Customer-initiated (first payment)
PaymentType::RECURRING;    // Subscription MIT
PaymentType::RETRY;        // Reattempt MIT
PaymentType::INSTALLMENT;  // Installment MIT
PaymentType::REBILL;       // Unscheduled MIT
PaymentType::MOTO;         // Mail/phone order (no card_cvv)
```

### `SolidGateConstants` — currency formatting

```php
use Lahiru\LaravelSolidGate\Support\SolidGateConstants;

SolidGateConstants::isZeroDecimalCurrency('JPY');   // true
SolidGateConstants::formatAmount(99.99, 'USD');     // 9999 (cents)
SolidGateConstants::parseAmount(9999, 'USD');       // 99.99
```

### `RefundReason` / `CancelSubscriptionReason`

Official reason codes — see [Refund Reasons](https://docs.solidgate.com/payments/payments-insights/refund-reasons/) and [Cancel Codes](https://docs.solidgate.com/billing/subscription-overview/subscription-insights/subscription-cancel-codes/).

---

## Troubleshooting

### `Platform is empty or invalid` (error `2.01`)

Add `platform` to your request or rely on the config default:

```php
'platform' => Platform::WEB,  // or MOB, APP
```

Config default: `SOLIDGATE_DEFAULT_PLATFORM=WEB`

### `Invalid IP` — private IP rejected

SolidGate rejects private IPs (`127.0.0.1`, `192.168.x.x`, etc.). When testing locally:

```php
'ip_address' => '8.8.8.8',  // sandbox only — use real client IP in production
```

In production, always pass the customer's public IP from the frontend or a trusted proxy header.

### HTTP 200 but payment failed

SolidGate returns HTTP 200 with an `error` object for validation failures. **Always check `isSuccessful()`** — do not rely on HTTP status alone.

```php
if (! $response->isSuccessful()) {
    logger()->warning('SolidGate validation error', [
        'code'    => $response->get('error.code'),
        'message' => $response->getErrorMessage(),
    ]);
}
```

### Wrong API base URL

| Symptom | Fix |
|---------|-----|
| Subscription call hits pay API | Use `SolidGate::retrieveSubscription()` — routes automatically |
| APM call fails with wrong host | Use `initializeAlternativePayment()` — uses gate API |
| Custom endpoint needed | Use `SolidGate::send()`, `gate()`, or `subscriptions()` |

```php
// Direct access when no helper exists
SolidGate::send('charge', $attributes);                              // pay API
SolidGate::subscriptions('subscription/status', ['subscription_id' => $id]);
SolidGate::gate('v1/status', ['order_id' => $orderId]);            // gate API
```

### Enable request logging

```env
SOLIDGATE_LOG_REQUESTS=true
```

Logs URL, method, status, and response to Laravel's log channel at `debug` level.

---

## API Reference Map

| Category | Base URL | Key methods |
|----------|----------|-------------|
| Card payments | `pay.solidgate.com/api/v1/` | `charge`, `auth`, `recurring`, `refund`, `void`, `settle`, `status` |
| Alternative payments | `gate.solidgate.com/api/` | `initializeAlternativePayment`, `recurringAlternativePayment` |
| Subscriptions | `subscriptions.solidgate.com/api/v1/` | `retrieveSubscription`, `cancelSubscription`, `createProduct` |
| Taxes | `subscriptions.solidgate.com/api/v1/` | `createTransactionalTax`, `downloadTransactionalTax` |
| Reporting | `reports.solidgate.com/` | `getCardOrdersReport`, `getRoutingEventsReport` |
| Checkout | `pay.solidgate.com/api/v1/` | `createPaymentPage`, `createPaymentLink` |
| Webhooks | `pay.solidgate.com/api/v1/` | `createWebhookEndpoint`, `listWebhookEndpoints` |

Full API spec: [api-docs.solidgate.com](https://api-docs.solidgate.com/)

---

## Development

```bash
git clone https://github.com/Lprabodha/laravel-solidgate.git
cd laravel-solidgate
composer install
cp .env.example .env   # never commit real keys
```

```bash
composer test      # run PHPUnit
composer format    # run Laravel Pint
composer analyse   # run PHPStan
```

---

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for full history.

**1.0.2** — APM gate endpoints, `PaymentType`/`Platform` helpers, error formatting, routing reports.

**1.0.1** — HMAC signing fix, endpoint path corrections, webhook validation, unit tests.

---

## License & References

MIT — see [LICENSE](LICENSE).

| Resource | Link |
|----------|------|
| API Reference | [api-docs.solidgate.com](https://api-docs.solidgate.com/) |
| Access & Signing | [docs.solidgate.com/payments/integrate/access-to-api](https://docs.solidgate.com/payments/integrate/access-to-api/) |
| Payment Guide | [docs.solidgate.com](https://docs.solidgate.com/) |
| Refund Reasons | [docs.solidgate.com/payments/payments-insights/refund-reasons](https://docs.solidgate.com/payments/payments-insights/refund-reasons/) |
| Cancel Codes | [docs.solidgate.com/billing/subscription-overview/subscription-insights/subscription-cancel-codes](https://docs.solidgate.com/billing/subscription-overview/subscription-insights/subscription-cancel-codes/) |

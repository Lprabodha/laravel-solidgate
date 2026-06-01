# Laravel SolidGate

[![Latest Version](https://img.shields.io/badge/version-1.0.7-blue.svg)](https://packagist.org/packages/lahiru/laravel-solidgate)
[![PHP Version](https://img.shields.io/badge/php-8.2%2B-blue.svg)](https://php.net)
[![Laravel Version](https://img.shields.io/badge/laravel-10.x%20%7C%2011.x%20%7C%2012.x%20%7C%2013.x-red.svg)](https://laravel.com)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

Laravel package for the [SolidGate](https://docs.solidgate.com/) payment gateway. Use the **facade** or **dependency injection**, get **typed responses**, and call **74+ API endpoints** with correct HMAC-SHA512 signing and sensible defaults.

**Good for:** server-side card/APM charges, subscriptions, hosted checkout, embedded payment forms, webhooks, and reporting.

---

## Table of Contents

**Getting started**

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Choose your integration](#choose-your-integration)
- [Using the package in Laravel](#using-the-package-in-laravel)
- [Quick Start](#quick-start)

**Checkout (customer-facing)**

- [Checkout integrations](#checkout-integrations)
  - [Payment Page (hosted)](#payment-page-hosted)
  - [Payment Form (embedded)](#payment-form-embedded)

**Server-side API**

- [Card Payments](#card-payments)
- [Alternative Payments (APM)](#alternative-payments-apm)
- [Subscriptions](#subscriptions)
- [Products, Taxes & Reporting](#products-taxes--reporting)
- [Webhooks](#webhooks)

**Reference**

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
| **Checkout docs** | [Payment Page](#payment-page-hosted) and [Payment Form](#payment-form-embedded) with copy-paste Laravel examples |
| **Correct signing** | HMAC-SHA512 over exact JSON body — matches official PHP SDK |
| **Smart defaults** | Auto-fills `platform`, `payment_type`, and card expiry formatting |
| **Type-safe responses** | `SolidGateResponse` with dot-notation and error helpers |
| **Webhook support** | Signature verification middleware + Laravel events |
| **Helper classes** | `Platform`, `PaymentType`, `RefundReason`, currency utilities |
| **Laravel native** | HTTP client, service container, facade, config publishing |

---

## Requirements

- PHP 8.2+
- Laravel 10.x, 11.x, 12.x, or 13.x (Laravel 13 requires PHP 8.3+)

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

### Config file (`config/solidgate.php`)

After publishing, keys are read from `config('solidgate.public_key')` and `config('solidgate.secret_key')` (backed by `SOLIDGATE_*` env vars).

---

## Choose your integration

Pick the flow that matches your product. All options below use this package on the server; only Payment Form also needs frontend JS.

| Integration | Customer experience | Package methods | Best when |
|-------------|---------------------|-----------------|-----------|
| **[Payment Page](#payment-page-hosted)** | Redirect to Solidgate-hosted page | `createPaymentPage()`, `deactivatePaymentPage()` | Fastest setup, minimal frontend |
| **[Payment Form](#payment-form-embedded)** | Card fields on your site | `generateSignature()` + app helper for `merchantData` | Full UI control, no redirect |
| **Card API (`charge`)** | You collect PAN (PCI scope) | `charge()`, `auth()`, `recurring()` | Full backend control |
| **APM** | PayPal, Pix, etc. | `initializeAlternativePayment()` | Alternative payment methods |
| **Subscriptions** | Recurring billing | `retrieveSubscription()`, `cancelSubscription()`, … | SaaS / memberships |

---

## Using the package in Laravel

### Facade (typical)

```php
use Lahiru\LaravelSolidGate\Facades\SolidGate;

$response = SolidGate::charge([...]);
```

### Dependency injection (testable)

```php
use Lahiru\LaravelSolidGate\Contracts\SolidGateClientInterface;

public function __construct(
    protected SolidGateClientInterface $solidgate
) {}

$this->solidgate->charge([...]);
```

### Signing helper (Payment Form)

```php
$signature = SolidGate::generateSignature($jsonPayload);
```

Uses the same HMAC-SHA512 algorithm as outbound API requests and webhook verification.

---

## Quick Start

Three-minute path to a successful server-side charge. For hosted or embedded checkout, jump to [Checkout integrations](#checkout-integrations).

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

## Checkout integrations

Customer-facing checkout using Solidgate UI. For server-side card capture without Solidgate JS, see [Card Payments](#card-payments).

| | Payment Page | Payment Form |
|---|--------------|--------------|
| **Docs** | [Payment Page](https://docs.solidgate.com/payments/integrate/payment-page/) | [Payment Form](https://docs.solidgate.com/payments/integrate/payment-form/) |
| **UX** | Redirect to hosted URL | Embedded on your page |
| **Frontend** | None required | `solid-form.js` + `PaymentFormSdk.init()` |
| **Backend** | `createPaymentPage()` | `generateSignature()` + encrypt `paymentIntent` |
| **PCI** | Card data on Solidgate | Card data tokenized by Solidgate iframe |

---

### Payment Page (hosted)

**When to use:** you want checkout live quickly with almost no frontend work.

**Flow**

1. Laravel calls `SolidGate::createPaymentPage(['order' => [...], 'page_customization' => [...]])`.
2. API returns a `url` (expires in ~24 hours).
3. Redirect the customer with `redirect()->away($url)`.
4. After payment, Solidgate sends the user to your `success_url` or `fail_url`.

**API**

| Method | Endpoint | Purpose |
|--------|----------|---------|
| `createPaymentPage($attributes)` | `POST /init` | Create hosted page, get URL |
| `deactivatePaymentPage($pageId)` | `POST /deactivate` | Invalidate page before expiry |

**Minimal example**

```php
use Illuminate\Support\Str;
use Lahiru\LaravelSolidGate\Facades\SolidGate;
use Lahiru\LaravelSolidGate\Support\Platform;

$response = SolidGate::createPaymentPage([
    'order' => [
        'order_id'          => (string) Str::uuid(),
        'amount'            => 10000,
        'currency'          => 'USD',
        'order_description' => 'Premium package',
        'customer_email'    => $request->user()->email,
        'ip_address'        => $request->ip(),
        'platform'          => Platform::WEB,
        'success_url'       => route('checkout.success'),
        'fail_url'          => route('checkout.fail'),
    ],
]);

if (! $response->isSuccessful()) {
    return back()->withErrors(['payment' => $response->getErrorMessage()]);
}

return redirect()->away($response->get('url'));
```

**Controller + route**

```php
// app/Http/Controllers/PaymentPageController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Lahiru\LaravelSolidGate\Facades\SolidGate;
use Lahiru\LaravelSolidGate\Support\Platform;

class PaymentPageController extends Controller
{
    public function checkout(Request $request)
    {
        $response = SolidGate::createPaymentPage([
            'order' => [
                'order_id'          => (string) Str::uuid(),
                'amount'            => $request->integer('amount'),
                'currency'          => $request->string('currency'),
                'order_description' => $request->string('description'),
                'customer_email'    => $request->user()->email,
                'ip_address'        => $request->ip(),
                'platform'          => Platform::WEB,
                'success_url'       => route('checkout.success'),
                'fail_url'          => route('checkout.fail'),
            ],
        ]);

        if (! $response->isSuccessful()) {
            return back()->withErrors(['payment' => $response->getErrorMessage()]);
        }

        return redirect()->away($response->get('url'));
    }
}
```

```php
// routes/web.php
Route::get('/checkout', [PaymentPageController::class, 'checkout'])->name('checkout.start');
```

**Subscriptions / catalog:** use `product_price_id` in `order` (from `SolidGate::getProductPrices()`). See [Create your payment page](https://docs.solidgate.com/payments/integrate/payment-page/create-your-payment-page/).

**Checklist**

- [ ] Unique `order_id` per checkout attempt
- [ ] Public `ip_address` (not `127.0.0.1` in production)
- [ ] `success_url` and `fail_url` registered and reachable
- [ ] Store `id` from response if you need `deactivatePaymentPage()` later

---

### Payment Form (embedded)

**When to use:** checkout must match your site design and stay on the same URL.

**Flow**

1. Build a `paymentIntent` array (amount, currency, `order_id`, customer, URLs, etc.).
2. Server returns `merchantData`: `merchant`, `signature`, `paymentIntent` (encrypted).
3. Blade loads `https://cdn.solidgate.com/js/solid-form.js` and calls `PaymentFormSdk.init({ merchantData })`.

**`merchantData` shape (passed to JS)**

| Key | Source |
|-----|--------|
| `merchant` | `config('solidgate.public_key')` |
| `signature` | `SolidGate::generateSignature($json)` |
| `paymentIntent` | AES-256-CBC encrypted JSON (see helper below) |

**App helper** — copy to `app/Services/SolidgatePaymentForm.php` (no extra Composer package):

```php
<?php

namespace App\Services;

use Lahiru\LaravelSolidGate\Facades\SolidGate;

class SolidgatePaymentForm
{
    public function merchantData(array $paymentIntent): array
    {
        $json = json_encode($paymentIntent, JSON_UNESCAPED_SLASHES);

        return [
            'merchant'      => config('solidgate.public_key'),
            'signature'     => SolidGate::generateSignature($json),
            'paymentIntent' => $this->encrypt($paymentIntent, config('solidgate.secret_key')),
        ];
    }

    private function encrypt(array $paymentIntent, string $secretKey): string
    {
        $payload = json_encode($paymentIntent, JSON_UNESCAPED_SLASHES);
        $key = substr($secretKey, 0, 32);
        $ivLen = openssl_cipher_iv_length('aes-256-cbc');
        $iv = openssl_random_pseudo_bytes($ivLen);
        $encrypted = openssl_encrypt($payload, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);

        return str_replace(['+', '/'], ['-', '_'], base64_encode($iv.$encrypted));
    }
}
```

Encryption matches [Solidgate’s without-SDK PHP guide](https://docs.solidgate.com/payments/integrate/payment-form/create-your-payment-form/).

**Controller**

```php
namespace App\Http\Controllers;

use App\Services\SolidgatePaymentForm;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Lahiru\LaravelSolidGate\Support\Platform;

class PaymentFormController extends Controller
{
    public function __construct(protected SolidgatePaymentForm $paymentForm) {}

    public function checkout(Request $request)
    {
        $paymentIntent = [
            'order_id'          => (string) Str::uuid(),
            'amount'            => 10000,
            'currency'          => 'USD',
            'order_description' => 'Premium package',
            'customer_email'    => $request->user()->email,
            'ip_address'        => $request->ip(),
            'platform'          => Platform::WEB,
            'success_url'       => route('checkout.success'),
            'fail_url'          => route('checkout.fail'),
        ];

        return view('checkout.payment-form', [
            'merchantData' => $this->paymentForm->merchantData($paymentIntent),
        ]);
    }
}
```

Field reference: [paymentIntent object](https://docs.solidgate.com/payments/integrate/payment-form/create-your-payment-form/).

**Blade**

```html
<div id="solid-payment-form-container"></div>

<script src="https://cdn.solidgate.com/js/solid-form.js"></script>
<script>
  PaymentFormSdk.init({
    merchantData: @json($merchantData),
    formParams: { formTypeClass: 'default' },
  });
</script>
```

**Checklist**

- [ ] Never expose `SOLIDGATE_SECRET_KEY` to the browser
- [ ] New `order_id` on every payment attempt
- [ ] Public `ip_address`
- [ ] Load `solid-form.js` only once (duplicate script causes init warnings)
- [ ] Dynamic amount/plan changes: [update payment form](https://docs.solidgate.com/payments/integrate/payment-form/update-payment-form/)

> **Optional:** Official [`solidgate/php-sdk`](https://docs.solidgate.com/payments/integrate/payment-form/create-your-payment-form/) exposes `$api->formMerchantData($paymentIntent)` if you prefer not to maintain the encrypt helper.

---

## Card Payments

Server-side card processing (you send PAN/CVV from your backend). For Solidgate-hosted or embedded UI instead, see [Checkout integrations](#checkout-integrations).

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

// Payment links (shareable URL, separate from Payment Page)
SolidGate::createPaymentLink([...]);
SolidGate::deactivatePaymentLink($linkId);
// Hosted / embedded checkout: [Checkout integrations](#checkout-integrations)

// Risks & files
SolidGate::createFraudPreventionListItems(['items' => [...]]);
SolidGate::createDisputeRepresentment([...]);
SolidGate::createFile(['file_name' => 'document.pdf', 'file_type' => 'application/pdf', 'file_size' => 1024000]);
```

---

## Webhooks

Receive async events (`card_gate.order.updated`, chargebacks, etc.) on a Laravel route with signature verification.

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

### Payment Page or Form fails to load

| Issue | Fix |
|-------|-----|
| Form blank / init error | New `order_id`, public `ip_address`, required `paymentIntent` fields |
| `Invalid IP` on checkout | Use customer public IP in production; see below |
| Payment Form script warning | Load `solid-form.js` only once |
| Page URL expired | Links last ~24h; call `createPaymentPage()` again |

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
| Checkout (hosted page) | `pay.solidgate.com/api/v1/` | `createPaymentPage`, `deactivatePaymentPage` |
| Checkout (payment link) | `pay.solidgate.com/api/v1/` | `createPaymentLink`, `deactivatePaymentLink` |
| Payment Form signing | — (local) | `generateSignature()` + app encrypt helper — see [Payment Form](#payment-form-embedded) |
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
composer test           # PHPUnit
composer analyse        # PHPStan static analysis
composer format         # fix code style (Laravel Pint)
composer format:check   # verify code style without fixing
```

CI runs on every push and pull request via [GitHub Actions](.github/workflows/ci.yml):

| Job | Checks |
|-----|--------|
| **tests** | PHPUnit on PHP 8.2 (Laravel 12) and PHP 8.3 (Laravel 13) |
| **static-analysis** | PHPStan level 5 |
| **code-style** | Laravel Pint |
| **security** | Composer audit (advisory) |

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
| Payment Page | [docs.solidgate.com/payments/integrate/payment-page](https://docs.solidgate.com/payments/integrate/payment-page/) |
| Payment Form | [docs.solidgate.com/payments/integrate/payment-form](https://docs.solidgate.com/payments/integrate/payment-form/) |
| Refund Reasons | [docs.solidgate.com/payments/payments-insights/refund-reasons](https://docs.solidgate.com/payments/payments-insights/refund-reasons/) |
| Cancel Codes | [docs.solidgate.com/billing/subscription-overview/subscription-insights/subscription-cancel-codes](https://docs.solidgate.com/billing/subscription-overview/subscription-insights/subscription-cancel-codes/) |

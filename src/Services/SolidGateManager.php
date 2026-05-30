<?php

namespace Lahiru\LaravelSolidGate\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Lahiru\LaravelSolidGate\Contracts\SolidGateClientInterface;
use Lahiru\LaravelSolidGate\Exceptions\SolidGateApiException;
use Lahiru\LaravelSolidGate\Exceptions\SolidGateConfigurationException;
use Lahiru\LaravelSolidGate\Responses\SolidGateResponse;
use Lahiru\LaravelSolidGate\Support\ErrorMessageFormatter;
use Lahiru\LaravelSolidGate\Support\SignatureValidator;

/**
 * SolidGate API Manager
 *
 * Handles all API interactions with SolidGate payment gateway.
 */
class SolidGateManager implements SolidGateClientInterface
{
    protected readonly string $publicKey;
    protected readonly string $secretKey;
    protected readonly array $config;
    protected readonly int $timeout;

    /**
     * Create a new SolidGate manager instance.
     *
     * @param  array  $config
     * @throws SolidGateConfigurationException
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->publicKey = $this->getConfigValue('public_key', 'SOLIDGATE_PUBLIC_KEY');
        $this->secretKey = $this->getConfigValue('secret_key', 'SOLIDGATE_SECRET_KEY');
        $this->timeout = (int) ($config['api']['timeout'] ?? 30);

        $this->validateConfiguration();
    }

    /**
     * Get configuration value with validation.
     *
     * @param  string  $key
     * @param  string  $envKey
     * @return string
     * @throws SolidGateConfigurationException
     */
    protected function getConfigValue(string $key, string $envKey): string
    {
        $value = $this->config[$key] ?? config("solidgate.{$key}") ?? env($envKey);

        if (empty($value)) {
            throw new SolidGateConfigurationException(
                "SolidGate {$key} is not configured. Please set {$envKey} in your .env file or configure it in config/solidgate.php."
            );
        }

        return (string) $value;
    }

    /**
     * Validate the configuration.
     *
     * @throws SolidGateConfigurationException
     */
    protected function validateConfiguration(): void
    {
        if (empty($this->publicKey)) {
            throw new SolidGateConfigurationException('SolidGate public key is required.');
        }

        if (empty($this->secretKey)) {
            throw new SolidGateConfigurationException('SolidGate secret key is required.');
        }

        if (empty($this->config['api']['base_url'] ?? '')) {
            throw new SolidGateConfigurationException('SolidGate API base URL is required.');
        }
    }

    /**
     * Create a charge transaction.
     *
     * @param  array  $attributes
     * @return SolidGateResponse
     * @throws SolidGateApiException
     */
    public function charge(array $attributes): SolidGateResponse
    {
        return $this->send('charge', $attributes);
    }

    /**
     * Create a recurring transaction.
     *
     * @param  array  $attributes
     * @return SolidGateResponse
     * @throws SolidGateApiException
     */
    public function recurring(array $attributes): SolidGateResponse
    {
        return $this->send('recurring', $attributes);
    }

    /**
     * Get transaction status.
     *
     * @param  array  $attributes
     * @return SolidGateResponse
     * @throws SolidGateApiException
     */
    public function status(array $attributes): SolidGateResponse
    {
        return $this->send('status', $attributes);
    }

    /**
     * Refund a transaction.
     *
     * @param  array  $attributes
     * @return SolidGateResponse
     * @throws SolidGateApiException
     */
    public function refund(array $attributes): SolidGateResponse
    {
        return $this->send('refund', $attributes);
    }

    /**
     * Void a transaction.
     *
     * @param  array  $attributes
     * @return SolidGateResponse
     * @throws SolidGateApiException
     */
    public function void(array $attributes): SolidGateResponse
    {
        return $this->send('void', $attributes);
    }

    /**
     * Settle a transaction.
     *
     * @param  array  $attributes
     * @return SolidGateResponse
     * @throws SolidGateApiException
     */
    public function settle(array $attributes): SolidGateResponse
    {
        return $this->send('settle', $attributes);
    }

    /**
     * Send a request to the SolidGate API.
     *
     * @param  string  $endpoint
     * @param  array  $attributes
     * @param  string  $method
     * @return SolidGateResponse
     * @throws SolidGateApiException
     */
    public function send(string $endpoint, array $attributes = [], string $method = 'POST'): SolidGateResponse
    {
        $baseUrl = rtrim($this->config['api']['base_url'] ?? '', '/');

        return $this->executeRequest($baseUrl, $endpoint, $attributes, $method);
    }

    /**
     * Extract error message from API response.
     *
     * @param  array  $responseData
     * @return string
     */
    protected function extractErrorMessage(array $responseData): string
    {
        return ErrorMessageFormatter::fromResponse($responseData);
    }

    /**
     * Create a new HTTP request instance.
     */
    protected function createHttpRequest(): PendingRequest
    {
        return Http::withOptions([
            'verify' => config('solidgate.api.verify_ssl', true),
        ]);
    }

    /**
     * Generate signature for API request.
     *
     * @see https://docs.solidgate.com/payments/integrate/access-to-api/
     *
     * @param  string  $payload  Request body JSON string, or empty string for GET/DELETE without body
     * @return string
     */
    public function generateSignature(string $payload): string
    {
        return SignatureValidator::make($this->publicKey, $payload, $this->secretKey);
    }

    /**
     * Encode request attributes to JSON matching the official Solidgate PHP SDK.
     *
     * @param  array  $attributes
     * @return string
     * @throws SolidGateApiException
     */
    protected function encodePayload(array $attributes): string
    {
        $payload = json_encode($attributes);

        if ($payload === false) {
            throw new SolidGateApiException('Failed to encode request body to JSON.');
        }

        return $payload;
    }

    /**
     * Resolve whether a request carries a JSON body and what to sign.
     *
     * @return array{0: string|null, 1: string}
     */
    protected function resolveRequestPayload(string $method, array $attributes): array
    {
        $httpMethod = strtoupper($method);

        if ($httpMethod === 'GET') {
            return [null, ''];
        }

        if ($httpMethod === 'DELETE' && $attributes === []) {
            return [null, ''];
        }

        $payload = $this->encodePayload($attributes);

        return [$payload, $payload];
    }

    /**
     * Execute an HTTP request against a Solidgate API base URL.
     *
     * @throws SolidGateApiException
     */
    protected function executeRequest(
        string $baseUrl,
        string $endpoint,
        array $attributes = [],
        string $method = 'POST'
    ): SolidGateResponse {
        $url = rtrim($baseUrl, '/') . '/' . ltrim($endpoint, '/');
        $httpMethod = strtoupper($method);
        [$body, $signaturePayload] = $this->resolveRequestPayload($method, $attributes);

        $headers = [
            'Accept' => 'application/json',
            'Merchant' => $this->publicKey,
            'Signature' => $this->generateSignature($signaturePayload),
        ];

        if ($body !== null) {
            $headers['Content-Type'] = 'application/json';
        }

        try {
            $request = $this->createHttpRequest()
                ->timeout($this->timeout)
                ->withHeaders($headers);

            $response = match ($httpMethod) {
                'GET' => empty($attributes)
                    ? $request->get($url)
                    : $request->get($url, $attributes),
                'PUT' => $request->withBody($body, 'application/json')->put($url),
                'PATCH' => $request->withBody($body, 'application/json')->patch($url),
                'DELETE' => $body === null
                    ? $request->delete($url)
                    : $request->withBody($body, 'application/json')->delete($url),
                default => $request->withBody($body, 'application/json')->post($url),
            };

            $statusCode = $response->status();
            $responseData = $response->json() ?? ['raw' => $response->body()];

            if (config('solidgate.log_requests', false)) {
                Log::debug('SolidGate API Request', [
                    'url' => $url,
                    'endpoint' => $endpoint,
                    'method' => $httpMethod,
                    'status' => $statusCode,
                    'response' => $responseData,
                ]);
            }

            if ($statusCode >= 400) {
                $errorMessage = $this->extractErrorMessage($responseData);
                throw new SolidGateApiException(
                    "SolidGate API error: {$errorMessage}",
                    $responseData,
                    $statusCode
                );
            }

            return new SolidGateResponse($responseData, $statusCode, $response->headers());
        } catch (RequestException $e) {
            $responseData = $e->response?->json() ?? ['error' => $e->getMessage()];
            throw new SolidGateApiException(
                'SolidGate request failed: ' . $e->getMessage(),
                $responseData,
                $e->response?->status() ?? 0,
                $e
            );
        }
    }

    /**
     * Send request to subscriptions API.
     *
     * @param  string  $endpoint
     * @param  array  $attributes
     * @param  string  $method
     * @return SolidGateResponse
     * @throws SolidGateApiException
     */
    public function subscriptions(string $endpoint, array $attributes = [], string $method = 'POST'): SolidGateResponse
    {
        $baseUrl = rtrim($this->config['api']['subscriptions_url'] ?? 'https://subscriptions.solidgate.com/api/v1/', '/');
        return $this->sendToUrl($baseUrl, $endpoint, $attributes, $method);
    }

    /**
     * Send request to gate API (alternative payments).
     *
     * @param  string  $endpoint
     * @param  array  $attributes
     * @param  string  $method
     * @return SolidGateResponse
     * @throws SolidGateApiException
     */
    public function gate(string $endpoint, array $attributes = [], string $method = 'POST'): SolidGateResponse
    {
        $baseUrl = rtrim($this->config['api']['gate_url'] ?? 'https://gate.solidgate.com/api/', '/');
        return $this->sendToUrl($baseUrl, $endpoint, $attributes, $method);
    }

    /**
     * Send request to a specific URL.
     *
     * @param  string  $baseUrl
     * @param  string  $endpoint
     * @param  array  $attributes
     * @param  string  $method
     * @return SolidGateResponse
     * @throws SolidGateApiException
     */
    protected function sendToUrl(string $baseUrl, string $endpoint, array $attributes = [], string $method = 'POST'): SolidGateResponse
    {
        return $this->executeRequest($baseUrl, $endpoint, $attributes, $method);
    }

    /**
     * Cancel a subscription.
     *
     * @param  string  $subscriptionId
     * @param  string  $cancelCode
     * @param  bool  $force
     * @return SolidGateResponse
     * @throws SolidGateApiException
     */
    public function cancelSubscription(string $subscriptionId, string $cancelCode, bool $force = false): SolidGateResponse
    {
        return $this->subscriptions('subscription/cancel', [
            'subscription_id' => $subscriptionId,
            'force' => $force,
            'cancel_code' => $cancelCode,
        ]);
    }

    /**
     * Retrieve subscription status.
     *
     * @param  string  $subscriptionId
     * @return SolidGateResponse
     * @throws SolidGateApiException
     */
    public function retrieveSubscription(string $subscriptionId): SolidGateResponse
    {
        return $this->subscriptions('subscription/status', [
            'subscription_id' => $subscriptionId,
        ]);
    }

    /**
     * Switch subscription product.
     *
     * @param  string  $subscriptionId
     * @param  string  $productId
     * @return SolidGateResponse
     * @throws SolidGateApiException
     */
    public function switchSubscriptionProduct(string $subscriptionId, string $productId): SolidGateResponse
    {
        return $this->subscriptions('subscription/switch-subscription-product', [
            'subscription_id' => $subscriptionId,
            'new_product_id' => $productId,
        ]);
    }

    /**
     * Update subscription.
     *
     * @param  string  $subscriptionId
     * @param  array  $attributes
     * @return SolidGateResponse
     * @throws SolidGateApiException
     */
    public function updateSubscription(string $subscriptionId, array $attributes = []): SolidGateResponse
    {
        $payload = array_merge(['id' => $subscriptionId], $attributes);
        return $this->subscriptions('subscription/update', $payload);
    }

    /**
     * Restore subscription.
     *
     * @param  string  $subscriptionId
     * @param  string|null  $expireDate
     * @return SolidGateResponse
     * @throws SolidGateApiException
     */
    public function restoreSubscription(string $subscriptionId, ?string $expireDate = null): SolidGateResponse
    {
        $payload = ['subscription_id' => $subscriptionId];
        if ($expireDate) {
            $payload['expired_at'] = $expireDate;
        }
        return $this->subscriptions('subscription/restore', $payload);
    }

    /**
     * Get subscription list by customer.
     *
     * @param  string  $customerId
     * @return SolidGateResponse
     * @throws SolidGateApiException
     */
    public function getSubscriptionList(string $customerId): SolidGateResponse
    {
        return $this->subscriptions('subscription/list', [
            'customer_account_id' => $customerId,
        ]);
    }

    /**
     * Update payment method token for subscription.
     *
     * @param  string  $subscriptionId
     * @param  string  $token
     * @return SolidGateResponse
     * @throws SolidGateApiException
     */
    public function updatePaymentMethodToken(string $subscriptionId, string $token): SolidGateResponse
    {
        return $this->subscriptions('subscription/update-token', [
            'subscription_id' => $subscriptionId,
            'token' => $token,
        ]);
    }

    /**
     * Create subscription pause.
     *
     * @param  string  $subscriptionId
     * @param  string  $stopDate
     * @param  string|null  $startDate
     * @return SolidGateResponse
     * @throws SolidGateApiException
     */
    public function createSubscriptionPause(string $subscriptionId, string $stopDate, ?string $startDate = null): SolidGateResponse
    {
        $payload = [
            'start_point' => [
                'type' => $startDate ? 'specific_date' : 'immediate',
            ],
            'stop_point' => [
                'type' => 'specific_date',
                'date' => $stopDate,
            ],
        ];

        if ($startDate) {
            $payload['start_point']['date'] = $startDate;
        }

        return $this->subscriptions("subscriptions/{$subscriptionId}/pause-schedule", $payload);
    }

    /**
     * Get order status from pay API.
     *
     * @param  string  $orderId
     * @return SolidGateResponse
     * @throws SolidGateApiException
     */
    public function getOrderStatus(string $orderId): SolidGateResponse
    {
        return $this->status(['order_id' => $orderId]);
    }

    /**
     * Get order status from gate API.
     *
     * @param  string  $orderId
     * @return SolidGateResponse
     * @throws SolidGateApiException
     */
    public function getAlternativePaymentOrderStatus(string $orderId): SolidGateResponse
    {
        return $this->gate('v1/status', ['order_id' => $orderId]);
    }

    /**
     * Void a transaction by order ID.
     *
     * @param  string  $orderId
     * @return SolidGateResponse
     * @throws SolidGateApiException
     */
    public function voidTransaction(string $orderId): SolidGateResponse
    {
        return $this->void(['order_id' => $orderId]);
    }

    /**
     * Settle a transaction by order ID.
     *
     * @param  string  $orderId
     * @param  int  $amount
     * @return SolidGateResponse
     * @throws SolidGateApiException
     */
    public function settleTransaction(string $orderId, int $amount): SolidGateResponse
    {
        return $this->settle([
            'order_id' => $orderId,
            'amount' => $amount,
        ]);
    }

    /**
     * Process full refund.
     *
     * @param  string  $orderId
     * @param  int  $amount
     * @param  string  $method
     * @param  string|null  $refundReasonCode
     * @return SolidGateResponse
     * @throws SolidGateApiException
     */
    public function processFullRefund(string $orderId, int $amount, string $method = 'card', ?string $refundReasonCode = null): SolidGateResponse
    {
        $baseUrl = $method === 'card'
            ? rtrim($this->config['api']['base_url'] ?? 'https://pay.solidgate.com/api/v1/', '/')
            : rtrim($this->config['api']['gate_url'] ?? 'https://gate.solidgate.com/api/', '/');

        $payload = [
            'order_id' => $orderId,
            'amount' => $amount,
        ];

        if ($refundReasonCode) {
            $payload['refund_reason_code'] = $refundReasonCode;
        }

        $endpoint = $method === 'card' ? 'refund' : 'v1/refund';

        return $this->sendToUrl($baseUrl, $endpoint, $payload);
    }

    /**
     * Process partial refund.
     *
     * @param  string  $orderId
     * @param  int  $amount
     * @param  string  $method
     * @param  string|null  $refundReasonCode
     * @return SolidGateResponse
     * @throws SolidGateApiException
     */
    public function processPartialRefund(string $orderId, int $amount, string $method = 'card', ?string $refundReasonCode = null): SolidGateResponse
    {
        return $this->processFullRefund($orderId, $amount, $method, $refundReasonCode);
    }

    /**
     * Create a product.
     *
     * @param  array  $attributes
     * @return SolidGateResponse
     * @throws SolidGateApiException
     */
    public function createProduct(array $attributes): SolidGateResponse
    {
        return $this->subscriptions('products', $attributes);
    }

    /**
     * Create product price.
     *
     * @param  string  $productId
     * @param  array  $attributes
     * @return SolidGateResponse
     * @throws SolidGateApiException
     */
    public function createProductPrice(string $productId, array $attributes): SolidGateResponse
    {
        return $this->subscriptions("products/{$productId}/prices", $attributes);
    }

    /**
     * Get product prices.
     *
     * @param  string  $productId
     * @param  int  $limit
     * @param  int  $offset
     * @return SolidGateResponse
     * @throws SolidGateApiException
     */
    public function getProductPrices(string $productId, int $limit = 80, int $offset = 0): SolidGateResponse
    {
        return $this->subscriptions(
            "products/{$productId}/prices",
            [
                'pagination' => [
                    'limit' => $limit,
                    'offset' => $offset,
                ],
            ],
            'GET'
        );
    }

    /**
     * Update product price.
     *
     * @param  string  $productId
     * @param  string  $priceId
     * @param  array  $attributes
     * @return SolidGateResponse
     * @throws SolidGateApiException
     */
    public function updateProductPrice(string $productId, string $priceId, array $attributes): SolidGateResponse
    {
        return $this->subscriptions("products/{$productId}/prices/{$priceId}", $attributes, 'PATCH');
    }

    // ========================================================================
    // Card Payments - Additional Endpoints
    // ========================================================================

    /**
     * Process Google Pay payment.
     *
     * @param  array  $attributes
     * @return SolidGateResponse
     * @throws SolidGateApiException
     */
    public function chargeWithGooglePay(array $attributes): SolidGateResponse
    {
        return $this->send('google-pay', $attributes);
    }

    /**
     * Process Apple Pay payment.
     *
     * @param  array  $attributes
     * @return SolidGateResponse
     * @throws SolidGateApiException
     */
    public function chargeWithApplePay(array $attributes): SolidGateResponse
    {
        return $this->send('apple-pay', $attributes);
    }

    /**
     * Create incremental authorization.
     *
     * @param  array  $attributes
     * @return SolidGateResponse
     * @throws SolidGateApiException
     */
    public function createIncrementalAuth(array $attributes): SolidGateResponse
    {
        return $this->send('increment', $attributes);
    }

    /**
     * Resign a transaction.
     *
     * @param  array  $attributes
     * @return SolidGateResponse
     * @throws SolidGateApiException
     */
    public function resignTransaction(array $attributes): SolidGateResponse
    {
        return $this->send('resign', $attributes);
    }

    /**
     * Get ARN codes for a transaction.
     *
     * @param  array  $attributes
     * @return SolidGateResponse
     * @throws SolidGateApiException
     */
    public function getArnCodes(array $attributes): SolidGateResponse
    {
        return $this->send('arn-code', $attributes);
    }

    // ========================================================================
    // Alternative Payment Methods
    // ========================================================================

    /**
     * Initialize alternative payment method.
     *
     * @see https://api-docs.solidgate.com/#tag/Alternative-payment-methods/operation/init-apm-payment
     *
     * @param  array  $attributes
     * @return SolidGateResponse
     * @throws SolidGateApiException
     */
    public function initializeAlternativePayment(array $attributes): SolidGateResponse
    {
        return $this->gate('v1/init-payment', $attributes);
    }

    /**
     * Process token-based recurring alternative payment.
     *
     * @see https://api-docs.solidgate.com/#tag/Alternative-payment-methods/operation/recurring-apm-payment
     *
     * @param  array  $attributes
     * @return SolidGateResponse
     * @throws SolidGateApiException
     */
    public function recurringAlternativePayment(array $attributes): SolidGateResponse
    {
        return $this->gate('v1/recurring', $attributes);
    }

    /**
     * Revoke recurring token for alternative payment.
     *
     * @param  array  $attributes
     * @return SolidGateResponse
     * @throws SolidGateApiException
     */
    public function revokeRecurringToken(array $attributes): SolidGateResponse
    {
        return $this->gate('v1/recurring-token/cancel', $attributes);
    }

    // ========================================================================
    // Products and Prices - Additional Endpoints
    // ========================================================================

    /**
     * Get product list.
     *
     * @param  array  $filters
     * @return SolidGateResponse
     * @throws SolidGateApiException
     */
    public function getProductList(array $filters = []): SolidGateResponse
    {
        return $this->subscriptions('products', $filters, 'GET');
    }

    /**
     * Get product by ID.
     *
     * @param  string  $productId
     * @return SolidGateResponse
     * @throws SolidGateApiException
     */
    public function getProduct(string $productId): SolidGateResponse
    {
        return $this->subscriptions("products/{$productId}", [], 'GET');
    }

    /**
     * Update product.
     *
     * @param  string  $productId
     * @param  array  $attributes
     * @return SolidGateResponse
     * @throws SolidGateApiException
     */
    public function updateProduct(string $productId, array $attributes): SolidGateResponse
    {
        return $this->subscriptions("products/{$productId}", $attributes, 'PATCH');
    }

    /**
     * Archive product.
     *
     * @param  string  $productId
     * @return SolidGateResponse
     * @throws SolidGateApiException
     */
    public function archiveProduct(string $productId): SolidGateResponse
    {
        return $this->subscriptions("products/{$productId}/archive", [], 'POST');
    }

    /**
     * Retrieve product prices.
     *
     * @param  array  $attributes
     * @return SolidGateResponse
     * @throws SolidGateApiException
     */
    public function retrieveProductPrices(array $attributes): SolidGateResponse
    {
        return $this->subscriptions('products/prices/list', $attributes);
    }

    /**
     * Calculate product price.
     *
     * @param  array  $attributes
     * @return SolidGateResponse
     * @throws SolidGateApiException
     */
    public function calculateProductPrice(array $attributes): SolidGateResponse
    {
        return $this->subscriptions('products/calculatePrice', $attributes);
    }

    // ========================================================================
    // Taxes
    // ========================================================================

    /**
     * Create transactional tax.
     *
     * @param  array  $attributes
     * @return SolidGateResponse
     * @throws SolidGateApiException
     */
    public function createTransactionalTax(array $attributes): SolidGateResponse
    {
        return $this->subscriptions('transactional', $attributes);
    }

    /**
     * Download transactional tax.
     *
     * @param  array  $filters
     * @return SolidGateResponse
     * @throws SolidGateApiException
     */
    public function downloadTransactionalTax(string $reportId): SolidGateResponse
    {
        return $this->subscriptions("transactional/report/{$reportId}/download", [], 'GET');
    }

    /**
     * Create summary tax.
     *
     * @param  array  $attributes
     * @return SolidGateResponse
     * @throws SolidGateApiException
     */
    public function createSummaryTax(array $attributes): SolidGateResponse
    {
        return $this->subscriptions('summary', $attributes);
    }

    /**
     * Download summary tax.
     *
     * @param  array  $filters
     * @return SolidGateResponse
     * @throws SolidGateApiException
     */
    public function downloadSummaryTax(string $reportId): SolidGateResponse
    {
        return $this->subscriptions("summary/report/{$reportId}/download", [], 'GET');
    }

    // ========================================================================
    // Subscriptions - Additional Endpoints
    // ========================================================================

    /**
     * Update subscription pause.
     *
     * @param  string  $subscriptionId
     * @param  array  $attributes
     * @return SolidGateResponse
     * @throws SolidGateApiException
     */
    public function updateSubscriptionPause(string $subscriptionId, array $attributes): SolidGateResponse
    {
        return $this->subscriptions("subscriptions/{$subscriptionId}/pause-schedule", $attributes, 'PATCH');
    }

    /**
     * Remove subscription pause.
     *
     * @param  string  $subscriptionId
     * @return SolidGateResponse
     * @throws SolidGateApiException
     */
    public function removeSubscriptionPause(string $subscriptionId): SolidGateResponse
    {
        return $this->subscriptions("subscriptions/{$subscriptionId}/pause-schedule", [], 'DELETE');
    }

    /**
     * Cancel subscriptions by customer.
     *
     * @param  string  $customerId
     * @param  string  $cancelCode
     * @param  bool  $force
     * @return SolidGateResponse
     * @throws SolidGateApiException
     */
    public function cancelSubscriptionsByCustomer(string $customerId, string $cancelCode, bool $force = false): SolidGateResponse
    {
        return $this->subscriptions('subscription/cancel-by-customer', [
            'customer_account_id' => $customerId,
            'force' => $force,
            'cancel_code' => $cancelCode,
        ]);
    }

    /**
     * List invoices by subscription.
     *
     * @param  string  $subscriptionId
     * @param  array  $filters
     * @return SolidGateResponse
     * @throws SolidGateApiException
     */
    public function listInvoicesBySubscription(string $subscriptionId, array $filters = []): SolidGateResponse
    {
        return $this->subscriptions('subscription/invoice/list', array_merge([
            'subscription_id' => $subscriptionId,
        ], $filters));
    }

    /**
     * List orders by invoice ID.
     *
     * @param  string  $invoiceId
     * @param  array  $filters
     * @return SolidGateResponse
     * @throws SolidGateApiException
     */
    public function listOrdersByInvoice(string $invoiceId, array $filters = []): SolidGateResponse
    {
        return $this->subscriptions('subscription/order/list', array_merge([
            'invoice_id' => $invoiceId,
        ], $filters));
    }

    // ========================================================================
    // Risks
    // ========================================================================

    /**
     * Create fraud prevention list items.
     *
     * @param  array  $attributes
     * @return SolidGateResponse
     * @throws SolidGateApiException
     */
    public function createFraudPreventionListItems(array $attributes): SolidGateResponse
    {
        $baseUrl = rtrim($this->config['api']['reports_url'] ?? 'https://reports.solidgate.com/', '/');
        return $this->sendToUrl($baseUrl, 'fraud-prevention-list-items/create', $attributes);
    }

    /**
     * List fraud prevention list items.
     *
     * @param  array  $filters
     * @return SolidGateResponse
     * @throws SolidGateApiException
     */
    public function listFraudPreventionListItems(array $filters = []): SolidGateResponse
    {
        $baseUrl = rtrim($this->config['api']['reports_url'] ?? 'https://reports.solidgate.com/', '/');
        return $this->sendToUrl($baseUrl, 'fraud-prevention-list-items/list', $filters);
    }

    /**
     * Delete fraud prevention list item.
     *
     * @param  string  $itemId
     * @return SolidGateResponse
     * @throws SolidGateApiException
     */
    public function deleteFraudPreventionListItem(string $itemId): SolidGateResponse
    {
        $baseUrl = rtrim($this->config['api']['reports_url'] ?? 'https://reports.solidgate.com/', '/');
        return $this->sendToUrl($baseUrl, "fraud-prevention-list-items/{$itemId}", [], 'DELETE');
    }

    /**
     * Create dispute representment.
     *
     * @param  array  $attributes
     * @return SolidGateResponse
     * @throws SolidGateApiException
     */
    public function createDisputeRepresentment(array $attributes): SolidGateResponse
    {
        $baseUrl = rtrim($this->config['api']['reports_url'] ?? 'https://reports.solidgate.com/', '/');
        return $this->sendToUrl($baseUrl, 'dispute-representments/create', $attributes);
    }

    /**
     * Enrich dispute representment.
     *
     * @param  array  $attributes
     * @return SolidGateResponse
     * @throws SolidGateApiException
     */
    public function enrichDisputeRepresentment(array $attributes): SolidGateResponse
    {
        $baseUrl = rtrim($this->config['api']['reports_url'] ?? 'https://reports.solidgate.com/', '/');
        return $this->sendToUrl($baseUrl, 'dispute-representments/enrich', $attributes);
    }

    // ========================================================================
    // Checkout Solutions
    // ========================================================================

    /**
     * Create payment page.
     *
     * @param  array  $attributes
     * @return SolidGateResponse
     * @throws SolidGateApiException
     */
    public function createPaymentPage(array $attributes): SolidGateResponse
    {
        $baseUrl = rtrim($this->config['api']['base_url'] ?? '', '/');
        return $this->sendToUrl($baseUrl, 'init', $attributes);
    }

    /**
     * Deactivate payment page.
     *
     * @param  string  $pageId
     * @return SolidGateResponse
     * @throws SolidGateApiException
     */
    public function deactivatePaymentPage(string $pageId): SolidGateResponse
    {
        $baseUrl = rtrim($this->config['api']['base_url'] ?? '', '/');
        return $this->sendToUrl($baseUrl, 'deactivate', ['id' => $pageId]);
    }

    /**
     * Create payment link.
     *
     * @param  array  $attributes
     * @return SolidGateResponse
     * @throws SolidGateApiException
     */
    public function createPaymentLink(array $attributes): SolidGateResponse
    {
        $baseUrl = rtrim($this->config['api']['base_url'] ?? '', '/');
        return $this->sendToUrl($baseUrl, 'link/init', $attributes);
    }

    /**
     * Deactivate payment link.
     *
     * @param  string  $linkId
     * @return SolidGateResponse
     * @throws SolidGateApiException
     */
    public function deactivatePaymentLink(string $linkId): SolidGateResponse
    {
        $baseUrl = rtrim($this->config['api']['base_url'] ?? '', '/');
        return $this->sendToUrl($baseUrl, 'link/deactivate', ['id' => $linkId]);
    }

    // ========================================================================
    // Webhook Management
    // ========================================================================

    /**
     * Create webhook endpoint.
     *
     * @param  array  $attributes
     * @return SolidGateResponse
     * @throws SolidGateApiException
     */
    public function createWebhookEndpoint(array $attributes): SolidGateResponse
    {
        $baseUrl = rtrim($this->config['api']['base_url'] ?? '', '/');
        return $this->sendToUrl($baseUrl, 'webhooks/endpoints', $attributes);
    }

    /**
     * List webhook endpoints.
     *
     * @param  array  $filters
     * @return SolidGateResponse
     * @throws SolidGateApiException
     */
    public function listWebhookEndpoints(array $filters = []): SolidGateResponse
    {
        $baseUrl = rtrim($this->config['api']['base_url'] ?? '', '/');
        return $this->sendToUrl($baseUrl, 'webhooks/endpoints', $filters, 'GET');
    }

    /**
     * Update webhook endpoint.
     *
     * @param  string  $webhookId
     * @param  array  $attributes
     * @return SolidGateResponse
     * @throws SolidGateApiException
     */
    public function updateWebhookEndpoint(string $webhookId, array $attributes): SolidGateResponse
    {
        $baseUrl = rtrim($this->config['api']['base_url'] ?? '', '/');
        return $this->sendToUrl($baseUrl, "webhooks/endpoints/{$webhookId}", $attributes, 'PATCH');
    }

    /**
     * Delete webhook endpoint.
     *
     * @param  string  $webhookId
     * @return SolidGateResponse
     * @throws SolidGateApiException
     */
    public function deleteWebhookEndpoint(string $webhookId): SolidGateResponse
    {
        $baseUrl = rtrim($this->config['api']['base_url'] ?? '', '/');
        return $this->sendToUrl($baseUrl, "webhooks/endpoints/{$webhookId}", [], 'DELETE');
    }

    // ========================================================================
    // Reporting
    // ========================================================================

    /**
     * Get card orders report.
     *
     * @param  array  $filters
     * @return SolidGateResponse
     * @throws SolidGateApiException
     */
    public function getCardOrdersReport(array $filters): SolidGateResponse
    {
        $baseUrl = rtrim($this->config['api']['reports_url'] ?? 'https://reports.solidgate.com/', '/');
        return $this->sendToUrl($baseUrl, 'card-orders', $filters);
    }

    /**
     * Get APM orders report.
     *
     * @param  array  $filters
     * @return SolidGateResponse
     * @throws SolidGateApiException
     */
    public function getApmOrdersReport(array $filters): SolidGateResponse
    {
        $baseUrl = rtrim($this->config['api']['reports_url'] ?? 'https://reports.solidgate.com/', '/');
        return $this->sendToUrl($baseUrl, 'apm-orders', $filters);
    }

    /**
     * Get subscriptions report.
     *
     * @param  array  $filters
     * @return SolidGateResponse
     * @throws SolidGateApiException
     */
    public function getSubscriptionsReport(array $filters): SolidGateResponse
    {
        $baseUrl = rtrim($this->config['api']['reports_url'] ?? 'https://reports.solidgate.com/', '/');
        return $this->sendToUrl($baseUrl, 'subscriptions', $filters);
    }

    /**
     * Get chargebacks report.
     *
     * @param  array  $filters
     * @return SolidGateResponse
     * @throws SolidGateApiException
     */
    public function getChargebacksReport(array $filters): SolidGateResponse
    {
        $baseUrl = rtrim($this->config['api']['reports_url'] ?? 'https://reports.solidgate.com/', '/');
        return $this->sendToUrl($baseUrl, 'card-orders/chargebacks', $filters);
    }

    /**
     * Get PayPal disputes report.
     *
     * @param  array  $filters
     * @return SolidGateResponse
     * @throws SolidGateApiException
     */
    public function getPaypalDisputesReport(array $filters): SolidGateResponse
    {
        $baseUrl = rtrim($this->config['api']['reports_url'] ?? 'https://reports.solidgate.com/', '/');
        return $this->sendToUrl($baseUrl, 'apm-orders/paypal-disputes', $filters);
    }

    /**
     * Get prevention alerts report.
     *
     * @param  array  $filters
     * @return SolidGateResponse
     * @throws SolidGateApiException
     */
    public function getPreventionAlertsReport(array $filters): SolidGateResponse
    {
        $baseUrl = rtrim($this->config['api']['reports_url'] ?? 'https://reports.solidgate.com/', '/');
        return $this->sendToUrl($baseUrl, 'prevention_alerts', $filters);
    }

    /**
     * Download prevention alerts.
     *
     * @param  array  $filters
     * @return SolidGateResponse
     * @throws SolidGateApiException
     */
    public function downloadPreventionAlerts(string $reportId): SolidGateResponse
    {
        $baseUrl = rtrim($this->config['api']['reports_url'] ?? 'https://reports.solidgate.com/', '/');

        return $this->sendToUrl($baseUrl, "prevention_alerts/report/{$reportId}/download", [], 'GET');
    }

    /**
     * Get card fraud alerts report.
     *
     * @param  array  $filters
     * @return SolidGateResponse
     * @throws SolidGateApiException
     */
    public function getCardFraudAlertsReport(array $filters): SolidGateResponse
    {
        $baseUrl = rtrim($this->config['api']['reports_url'] ?? 'https://reports.solidgate.com/', '/');
        return $this->sendToUrl($baseUrl, 'card-orders/fraud-alerts', $filters);
    }

    /**
     * Get financial entries by date range.
     *
     * @param  array  $filters
     * @return SolidGateResponse
     * @throws SolidGateApiException
     */
    public function getFinancialEntriesByDateRange(array $filters): SolidGateResponse
    {
        $baseUrl = rtrim($this->config['api']['reports_url'] ?? 'https://reports.solidgate.com/', '/');
        return $this->sendToUrl($baseUrl, 'financial_entries', $filters);
    }

    /**
     * Download financial entries.
     *
     * @param  array  $filters
     * @return SolidGateResponse
     * @throws SolidGateApiException
     */
    public function downloadFinancialEntries(string $reportId): SolidGateResponse
    {
        $baseUrl = rtrim($this->config['api']['reports_url'] ?? 'https://reports.solidgate.com/', '/');

        return $this->sendToUrl($baseUrl, "financial_entries/report/{$reportId}/download", [], 'GET');
    }

    /**
     * Get routing events report.
     *
     * @see https://api-docs.solidgate.com/#tag/Reporting/operation/create-routing-events-report
     *
     * @param  array  $filters
     * @return SolidGateResponse
     * @throws SolidGateApiException
     */
    public function getRoutingEventsReport(array $filters): SolidGateResponse
    {
        $baseUrl = rtrim($this->config['api']['reports_url'] ?? 'https://reports.solidgate.com/', '/');

        return $this->sendToUrl($baseUrl, 'routing_events', $filters);
    }

    /**
     * Download routing events report.
     *
     * @see https://api-docs.solidgate.com/#tag/Reporting/operation/download-routing-events-report
     *
     * @param  string  $reportId
     * @return SolidGateResponse
     * @throws SolidGateApiException
     */
    public function downloadRoutingEvents(string $reportId): SolidGateResponse
    {
        $baseUrl = rtrim($this->config['api']['reports_url'] ?? 'https://reports.solidgate.com/', '/');

        return $this->sendToUrl($baseUrl, "routing_events/report/{$reportId}/download", [], 'GET');
    }

    // ========================================================================
    // Files
    // ========================================================================

    /**
     * Create file (upload file).
     *
     * @param  array  $attributes
     * @return SolidGateResponse
     * @throws SolidGateApiException
     */
    public function createFile(array $attributes): SolidGateResponse
    {
        $baseUrl = rtrim($this->config['api']['base_url'] ?? '', '/');
        return $this->sendToUrl($baseUrl, 'file/get-upload-url', $attributes);
    }
}
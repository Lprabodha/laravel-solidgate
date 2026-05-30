<?php

namespace Lahiru\LaravelSolidGate\Contracts;

use Lahiru\LaravelSolidGate\Responses\SolidGateResponse;

/**
 * Interface for SolidGate API client.
 */
interface SolidGateClientInterface
{
    /**
     * Create a charge transaction.
     *
     * @param  array  $attributes
     * @return SolidGateResponse
     */
    public function charge(array $attributes): SolidGateResponse;

    /**
     * Authorize a card without capturing funds.
     *
     * @param  array  $attributes
     * @return SolidGateResponse
     */
    public function auth(array $attributes): SolidGateResponse;

    /**
     * Create a recurring transaction.
     *
     * @param  array  $attributes
     * @return SolidGateResponse
     */
    public function recurring(array $attributes): SolidGateResponse;

    /**
     * Get transaction status.
     *
     * @param  array  $attributes
     * @return SolidGateResponse
     */
    public function status(array $attributes): SolidGateResponse;

    /**
     * Refund a transaction.
     *
     * @param  array  $attributes
     * @return SolidGateResponse
     */
    public function refund(array $attributes): SolidGateResponse;

    /**
     * Void a transaction.
     *
     * @param  array  $attributes
     * @return SolidGateResponse
     */
    public function void(array $attributes): SolidGateResponse;

    /**
     * Settle a transaction.
     *
     * @param  array  $attributes
     * @return SolidGateResponse
     */
    public function settle(array $attributes): SolidGateResponse;

    /**
     * Send a request to the SolidGate API.
     *
     * @param  string  $endpoint
     * @param  array  $attributes
     * @param  string  $method
     * @return SolidGateResponse
     */
    public function send(string $endpoint, array $attributes = [], string $method = 'POST'): SolidGateResponse;

    /**
     * Generate signature for API request.
     *
     * @param  string  $payload
     * @return string
     */
    public function generateSignature(string $payload): string;

    /**
     * Send request to subscriptions API.
     *
     * @param  string  $endpoint
     * @param  array  $attributes
     * @param  string  $method
     * @return SolidGateResponse
     */
    public function subscriptions(string $endpoint, array $attributes = [], string $method = 'POST'): SolidGateResponse;

    /**
     * Send request to gate API (alternative payments).
     *
     * @param  string  $endpoint
     * @param  array  $attributes
     * @param  string  $method
     * @return SolidGateResponse
     */
    public function gate(string $endpoint, array $attributes = [], string $method = 'POST'): SolidGateResponse;

    /**
     * Cancel a subscription.
     *
     * @param  string  $subscriptionId
     * @param  string  $cancelCode
     * @param  bool  $force
     * @return SolidGateResponse
     */
    public function cancelSubscription(string $subscriptionId, string $cancelCode, bool $force = false): SolidGateResponse;

    /**
     * Retrieve subscription status.
     *
     * @param  string  $subscriptionId
     * @return SolidGateResponse
     */
    public function retrieveSubscription(string $subscriptionId): SolidGateResponse;

    /**
     * Switch subscription product.
     *
     * @param  string  $subscriptionId
     * @param  string  $productId
     * @return SolidGateResponse
     */
    public function switchSubscriptionProduct(string $subscriptionId, string $productId): SolidGateResponse;

    /**
     * Update subscription.
     *
     * @param  string  $subscriptionId
     * @param  array  $attributes
     * @return SolidGateResponse
     */
    public function updateSubscription(string $subscriptionId, array $attributes = []): SolidGateResponse;

    /**
     * Restore subscription.
     *
     * @param  string  $subscriptionId
     * @param  string|null  $expireDate
     * @return SolidGateResponse
     */
    public function restoreSubscription(string $subscriptionId, ?string $expireDate = null): SolidGateResponse;

    /**
     * Get subscription list by customer.
     *
     * @param  string  $customerId
     * @return SolidGateResponse
     */
    public function getSubscriptionList(string $customerId): SolidGateResponse;

    /**
     * Update payment method token for subscription.
     *
     * @param  string  $subscriptionId
     * @param  string  $token
     * @return SolidGateResponse
     */
    public function updatePaymentMethodToken(string $subscriptionId, string $token): SolidGateResponse;

    /**
     * Create subscription pause.
     *
     * @param  string  $subscriptionId
     * @param  string  $stopDate
     * @param  string|null  $startDate
     * @return SolidGateResponse
     */
    public function createSubscriptionPause(string $subscriptionId, string $stopDate, ?string $startDate = null): SolidGateResponse;

    /**
     * Get order status from pay API.
     *
     * @param  string  $orderId
     * @return SolidGateResponse
     */
    public function getOrderStatus(string $orderId): SolidGateResponse;

    /**
     * Get order status from gate API.
     *
     * @param  string  $orderId
     * @return SolidGateResponse
     */
    public function getAlternativePaymentOrderStatus(string $orderId): SolidGateResponse;

    /**
     * Void a transaction by order ID.
     *
     * @param  string  $orderId
     * @return SolidGateResponse
     */
    public function voidTransaction(string $orderId): SolidGateResponse;

    /**
     * Settle a transaction by order ID.
     *
     * @param  string  $orderId
     * @param  int  $amount
     * @return SolidGateResponse
     */
    public function settleTransaction(string $orderId, int $amount): SolidGateResponse;

    /**
     * Process full refund.
     *
     * @param  string  $orderId
     * @param  int  $amount
     * @param  string  $method
     * @param  string|null  $refundReasonCode
     * @return SolidGateResponse
     */
    public function processFullRefund(string $orderId, int $amount, string $method = 'card', ?string $refundReasonCode = null): SolidGateResponse;

    /**
     * Process partial refund.
     *
     * @param  string  $orderId
     * @param  int  $amount
     * @param  string  $method
     * @param  string|null  $refundReasonCode
     * @return SolidGateResponse
     */
    public function processPartialRefund(string $orderId, int $amount, string $method = 'card', ?string $refundReasonCode = null): SolidGateResponse;

    /**
     * Create a product.
     *
     * @param  array  $attributes
     * @return SolidGateResponse
     */
    public function createProduct(array $attributes): SolidGateResponse;

    /**
     * Create product price.
     *
     * @param  string  $productId
     * @param  array  $attributes
     * @return SolidGateResponse
     */
    public function createProductPrice(string $productId, array $attributes): SolidGateResponse;

    /**
     * Get product prices.
     *
     * @param  string  $productId
     * @param  int  $limit
     * @param  int  $offset
     * @return SolidGateResponse
     */
    public function getProductPrices(string $productId, int $limit = 80, int $offset = 0): SolidGateResponse;

    /**
     * Update product price.
     *
     * @param  string  $productId
     * @param  string  $priceId
     * @param  array  $attributes
     * @return SolidGateResponse
     */
    public function updateProductPrice(string $productId, string $priceId, array $attributes): SolidGateResponse;

    // Card Payments - Additional Endpoints
    public function chargeWithGooglePay(array $attributes): SolidGateResponse;
    public function chargeWithApplePay(array $attributes): SolidGateResponse;
    public function createIncrementalAuth(array $attributes): SolidGateResponse;
    public function resignTransaction(array $attributes): SolidGateResponse;
    public function getArnCodes(array $attributes): SolidGateResponse;

    // Alternative Payment Methods
    public function initializeAlternativePayment(array $attributes): SolidGateResponse;
    public function recurringAlternativePayment(array $attributes): SolidGateResponse;
    public function revokeRecurringToken(array $attributes): SolidGateResponse;

    // Products and Prices - Additional Endpoints
    public function getProductList(array $filters = []): SolidGateResponse;
    public function getProduct(string $productId): SolidGateResponse;
    public function updateProduct(string $productId, array $attributes): SolidGateResponse;
    public function archiveProduct(string $productId): SolidGateResponse;
    public function retrieveProductPrices(array $attributes): SolidGateResponse;
    public function calculateProductPrice(array $attributes): SolidGateResponse;

    // Taxes
    public function createTransactionalTax(array $attributes): SolidGateResponse;
    public function downloadTransactionalTax(string $reportId): SolidGateResponse;
    public function createSummaryTax(array $attributes): SolidGateResponse;
    public function downloadSummaryTax(string $reportId): SolidGateResponse;

    // Subscriptions - Additional Endpoints
    public function updateSubscriptionPause(string $subscriptionId, array $attributes): SolidGateResponse;
    public function removeSubscriptionPause(string $subscriptionId): SolidGateResponse;
    public function cancelSubscriptionsByCustomer(string $customerId, string $cancelCode, bool $force = false): SolidGateResponse;
    public function listInvoicesBySubscription(string $subscriptionId, array $filters = []): SolidGateResponse;
    public function listOrdersByInvoice(string $invoiceId, array $filters = []): SolidGateResponse;

    // Risks
    public function createFraudPreventionListItems(array $attributes): SolidGateResponse;
    public function listFraudPreventionListItems(array $filters = []): SolidGateResponse;
    public function deleteFraudPreventionListItem(string $itemId): SolidGateResponse;
    public function createDisputeRepresentment(array $attributes): SolidGateResponse;
    public function enrichDisputeRepresentment(array $attributes): SolidGateResponse;

    // Checkout Solutions
    public function createPaymentPage(array $attributes): SolidGateResponse;
    public function deactivatePaymentPage(string $pageId): SolidGateResponse;
    public function createPaymentLink(array $attributes): SolidGateResponse;
    public function deactivatePaymentLink(string $linkId): SolidGateResponse;

    // Webhook Management
    public function createWebhookEndpoint(array $attributes): SolidGateResponse;
    public function listWebhookEndpoints(array $filters = []): SolidGateResponse;
    public function updateWebhookEndpoint(string $webhookId, array $attributes): SolidGateResponse;
    public function deleteWebhookEndpoint(string $webhookId): SolidGateResponse;

    // Reporting
    public function getCardOrdersReport(array $filters): SolidGateResponse;
    public function getApmOrdersReport(array $filters): SolidGateResponse;
    public function getSubscriptionsReport(array $filters): SolidGateResponse;
    public function getChargebacksReport(array $filters): SolidGateResponse;
    public function getPaypalDisputesReport(array $filters): SolidGateResponse;
    public function getPreventionAlertsReport(array $filters): SolidGateResponse;
    public function downloadPreventionAlerts(string $reportId): SolidGateResponse;
    public function getCardFraudAlertsReport(array $filters): SolidGateResponse;
    public function getFinancialEntriesByDateRange(array $filters): SolidGateResponse;
    public function downloadFinancialEntries(string $reportId): SolidGateResponse;
    public function getRoutingEventsReport(array $filters): SolidGateResponse;
    public function downloadRoutingEvents(string $reportId): SolidGateResponse;

    // Files
    public function createFile(array $attributes): SolidGateResponse;
}
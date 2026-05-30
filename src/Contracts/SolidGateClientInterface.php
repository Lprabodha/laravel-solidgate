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
     */
    public function charge(array $attributes): SolidGateResponse;

    /**
     * Authorize a card without capturing funds.
     */
    public function auth(array $attributes): SolidGateResponse;

    /**
     * Create a recurring transaction.
     */
    public function recurring(array $attributes): SolidGateResponse;

    /**
     * Get transaction status.
     */
    public function status(array $attributes): SolidGateResponse;

    /**
     * Refund a transaction.
     */
    public function refund(array $attributes): SolidGateResponse;

    /**
     * Void a transaction.
     */
    public function void(array $attributes): SolidGateResponse;

    /**
     * Settle a transaction.
     */
    public function settle(array $attributes): SolidGateResponse;

    /**
     * Send a request to the SolidGate API.
     */
    public function send(string $endpoint, array $attributes = [], string $method = 'POST'): SolidGateResponse;

    /**
     * Generate signature for API request.
     */
    public function generateSignature(string $payload): string;

    /**
     * Send request to subscriptions API.
     */
    public function subscriptions(string $endpoint, array $attributes = [], string $method = 'POST'): SolidGateResponse;

    /**
     * Send request to gate API (alternative payments).
     */
    public function gate(string $endpoint, array $attributes = [], string $method = 'POST'): SolidGateResponse;

    /**
     * Cancel a subscription.
     */
    public function cancelSubscription(string $subscriptionId, string $cancelCode, bool $force = false): SolidGateResponse;

    /**
     * Retrieve subscription status.
     */
    public function retrieveSubscription(string $subscriptionId): SolidGateResponse;

    /**
     * Switch subscription product.
     */
    public function switchSubscriptionProduct(string $subscriptionId, string $productId): SolidGateResponse;

    /**
     * Update subscription.
     */
    public function updateSubscription(string $subscriptionId, array $attributes = []): SolidGateResponse;

    /**
     * Restore subscription.
     */
    public function restoreSubscription(string $subscriptionId, ?string $expireDate = null): SolidGateResponse;

    /**
     * Get subscription list by customer.
     */
    public function getSubscriptionList(string $customerId): SolidGateResponse;

    /**
     * Update payment method token for subscription.
     */
    public function updatePaymentMethodToken(string $subscriptionId, string $token): SolidGateResponse;

    /**
     * Create subscription pause.
     */
    public function createSubscriptionPause(string $subscriptionId, string $stopDate, ?string $startDate = null): SolidGateResponse;

    /**
     * Get order status from pay API.
     */
    public function getOrderStatus(string $orderId): SolidGateResponse;

    /**
     * Get order status from gate API.
     */
    public function getAlternativePaymentOrderStatus(string $orderId): SolidGateResponse;

    /**
     * Void a transaction by order ID.
     */
    public function voidTransaction(string $orderId): SolidGateResponse;

    /**
     * Settle a transaction by order ID.
     */
    public function settleTransaction(string $orderId, int $amount): SolidGateResponse;

    /**
     * Process full refund.
     */
    public function processFullRefund(string $orderId, int $amount, string $method = 'card', ?string $refundReasonCode = null): SolidGateResponse;

    /**
     * Process partial refund.
     */
    public function processPartialRefund(string $orderId, int $amount, string $method = 'card', ?string $refundReasonCode = null): SolidGateResponse;

    /**
     * Create a product.
     */
    public function createProduct(array $attributes): SolidGateResponse;

    /**
     * Create product price.
     */
    public function createProductPrice(string $productId, array $attributes): SolidGateResponse;

    /**
     * Get product prices.
     */
    public function getProductPrices(string $productId, int $limit = 80, int $offset = 0): SolidGateResponse;

    /**
     * Update product price.
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

<?php

namespace Lahiru\LaravelSolidGate\Facades;

use Illuminate\Support\Facades\Facade;
use Lahiru\LaravelSolidGate\Contracts\SolidGateClientInterface;
use Lahiru\LaravelSolidGate\Responses\SolidGateResponse;

/**
 * SolidGate Facade
 *
 * @method static SolidGateResponse charge(array $attributes)
 * @method static SolidGateResponse recurring(array $attributes)
 * @method static SolidGateResponse status(array $attributes)
 * @method static SolidGateResponse refund(array $attributes)
 * @method static SolidGateResponse void(array $attributes)
 * @method static SolidGateResponse settle(array $attributes)
 * @method static SolidGateResponse send(string $endpoint, array $attributes = [], string $method = 'POST')
 * @method static string generateSignature(string $payload)
 * @method static SolidGateResponse subscriptions(string $endpoint, array $attributes = [], string $method = 'POST')
 * @method static SolidGateResponse gate(string $endpoint, array $attributes = [], string $method = 'POST')
 * @method static SolidGateResponse cancelSubscription(string $subscriptionId, string $cancelCode, bool $force = false)
 * @method static SolidGateResponse retrieveSubscription(string $subscriptionId)
 * @method static SolidGateResponse switchSubscriptionProduct(string $subscriptionId, string $productId)
 * @method static SolidGateResponse updateSubscription(string $subscriptionId, array $attributes = [])
 * @method static SolidGateResponse restoreSubscription(string $subscriptionId, ?string $expireDate = null)
 * @method static SolidGateResponse getSubscriptionList(string $customerId)
 * @method static SolidGateResponse updatePaymentMethodToken(string $subscriptionId, string $token)
 * @method static SolidGateResponse createSubscriptionPause(string $subscriptionId, string $stopDate, ?string $startDate = null)
 * @method static SolidGateResponse getOrderStatus(string $orderId)
 * @method static SolidGateResponse getAlternativePaymentOrderStatus(string $orderId)
 * @method static SolidGateResponse voidTransaction(string $orderId)
 * @method static SolidGateResponse settleTransaction(string $orderId, int $amount)
 * @method static SolidGateResponse processFullRefund(string $orderId, int $amount, string $method = 'card', ?string $refundReasonCode = null)
 * @method static SolidGateResponse processPartialRefund(string $orderId, int $amount, string $method = 'card', ?string $refundReasonCode = null)
 * @method static SolidGateResponse createProduct(array $attributes)
 * @method static SolidGateResponse createProductPrice(string $productId, array $attributes)
 * @method static SolidGateResponse getProductPrices(string $productId, int $limit = 80, int $offset = 0)
 * @method static SolidGateResponse updateProductPrice(string $productId, string $priceId, array $attributes)
 * @method static SolidGateResponse chargeWithGooglePay(array $attributes)
 * @method static SolidGateResponse chargeWithApplePay(array $attributes)
 * @method static SolidGateResponse createIncrementalAuth(array $attributes)
 * @method static SolidGateResponse resignTransaction(array $attributes)
 * @method static SolidGateResponse getArnCodes(array $attributes)
 * @method static SolidGateResponse initializeAlternativePayment(array $attributes)
 * @method static SolidGateResponse revokeRecurringToken(array $attributes)
 * @method static SolidGateResponse getProductList(array $filters = [])
 * @method static SolidGateResponse getProduct(string $productId)
 * @method static SolidGateResponse updateProduct(string $productId, array $attributes)
 * @method static SolidGateResponse archiveProduct(string $productId)
 * @method static SolidGateResponse retrieveProductPrices(array $attributes)
 * @method static SolidGateResponse calculateProductPrice(array $attributes)
 * @method static SolidGateResponse createTransactionalTax(array $attributes)
 * @method static SolidGateResponse downloadTransactionalTax(string $reportId)
 * @method static SolidGateResponse createSummaryTax(array $attributes)
 * @method static SolidGateResponse downloadSummaryTax(string $reportId)
 * @method static SolidGateResponse updateSubscriptionPause(string $subscriptionId, array $attributes)
 * @method static SolidGateResponse removeSubscriptionPause(string $subscriptionId)
 * @method static SolidGateResponse cancelSubscriptionsByCustomer(string $customerId, string $cancelCode, bool $force = false)
 * @method static SolidGateResponse listInvoicesBySubscription(string $subscriptionId, array $filters = [])
 * @method static SolidGateResponse listOrdersByInvoice(string $invoiceId, array $filters = [])
 * @method static SolidGateResponse createFraudPreventionListItems(array $attributes)
 * @method static SolidGateResponse listFraudPreventionListItems(array $filters = [])
 * @method static SolidGateResponse deleteFraudPreventionListItem(string $itemId)
 * @method static SolidGateResponse createDisputeRepresentment(array $attributes)
 * @method static SolidGateResponse enrichDisputeRepresentment(array $attributes)
 * @method static SolidGateResponse createPaymentPage(array $attributes)
 * @method static SolidGateResponse deactivatePaymentPage(string $pageId)
 * @method static SolidGateResponse createPaymentLink(array $attributes)
 * @method static SolidGateResponse deactivatePaymentLink(string $linkId)
 * @method static SolidGateResponse createWebhookEndpoint(array $attributes)
 * @method static SolidGateResponse listWebhookEndpoints(array $filters = [])
 * @method static SolidGateResponse updateWebhookEndpoint(string $webhookId, array $attributes)
 * @method static SolidGateResponse deleteWebhookEndpoint(string $webhookId)
 * @method static SolidGateResponse getCardOrdersReport(array $filters)
 * @method static SolidGateResponse getApmOrdersReport(array $filters)
 * @method static SolidGateResponse getSubscriptionsReport(array $filters)
 * @method static SolidGateResponse getChargebacksReport(array $filters)
 * @method static SolidGateResponse getPaypalDisputesReport(array $filters)
 * @method static SolidGateResponse getPreventionAlertsReport(array $filters)
 * @method static SolidGateResponse downloadPreventionAlerts(string $reportId)
 * @method static SolidGateResponse getCardFraudAlertsReport(array $filters)
 * @method static SolidGateResponse getFinancialEntriesByDateRange(array $filters)
 * @method static SolidGateResponse downloadFinancialEntries(string $reportId)
 * @method static SolidGateResponse createFile(array $attributes)
 *
 * @see \Lahiru\LaravelSolidGate\Services\SolidGateManager
 * @see \Lahiru\LaravelSolidGate\Contracts\SolidGateClientInterface
 * @see https://api-docs.solidgate.com/ Official SolidGate API Documentation
 */
class SolidGate extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'solidgate';
    }
}
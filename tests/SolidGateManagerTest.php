<?php

namespace Lahiru\LaravelSolidGate\Tests;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Lahiru\LaravelSolidGate\Events\SolidGateWebhookReceived;
use Lahiru\LaravelSolidGate\Http\Controllers\SolidGateWebhookController;
use Lahiru\LaravelSolidGate\Services\SolidGateManager;
use Lahiru\LaravelSolidGate\Support\PaymentType;
use Lahiru\LaravelSolidGate\Support\SignatureValidator;

class SolidGateManagerTest extends TestCase
{
    public function test_charge_sends_signed_json_body_to_pay_api(): void
    {
        Http::fake([
            'https://pay.solidgate.com/api/v1/charge' => Http::response(['order' => ['status' => 'processing']], 200),
        ]);

        $manager = new SolidGateManager($this->solidgateConfig());
        $attributes = [
            'amount' => 1000,
            'currency' => 'USD',
            'order_id' => 'order-123',
            'card_cvv' => '123',
        ];
        $expectedPayload = array_merge($attributes, [
            'platform' => 'WEB',
            'payment_type' => PaymentType::ONE_CLICK,
        ]);

        $response = $manager->charge($attributes);

        $this->assertTrue($response->isSuccessful());
        $this->assertSame('processing', $response->get('order.status'));

        Http::assertSent(function ($request) use ($expectedPayload) {
            $body = json_encode($expectedPayload);
            $expectedSignature = SignatureValidator::make('test-public', $body, 'test-secret');

            return $request->url() === 'https://pay.solidgate.com/api/v1/charge'
                && $request->method() === 'POST'
                && $request->body() === $body
                && $request->header('Merchant')[0] === 'test-public'
                && $request->header('Signature')[0] === $expectedSignature;
        });
    }

    public function test_charge_does_not_override_explicit_platform(): void
    {
        Http::fake([
            'https://pay.solidgate.com/api/v1/charge' => Http::response(['order' => ['status' => 'processing']], 200),
        ]);

        $manager = new SolidGateManager($this->solidgateConfig());
        $attributes = [
            'amount' => 1000,
            'currency' => 'USD',
            'order_id' => 'order-123',
            'platform' => 'MOB',
        ];

        $manager->charge($attributes);

        Http::assertSent(fn ($request) => str_contains($request->body(), '"platform":"MOB"'));
    }

    public function test_charge_normalizes_card_expiry_fields(): void
    {
        Http::fake([
            'https://pay.solidgate.com/api/v1/charge' => Http::response(['order' => ['status' => 'processing']], 200),
        ]);

        $manager = new SolidGateManager($this->solidgateConfig());
        $manager->charge([
            'amount' => 1000,
            'currency' => 'USD',
            'order_id' => 'order-123',
            'card_exp_month' => 3,
            'card_exp_year' => '2030',
        ]);

        Http::assertSent(function ($request) {
            $payload = json_decode($request->body(), true);

            return $payload['card_exp_month'] === '03'
                && $payload['card_exp_year'] === 2030;
        });
    }

    public function test_status_refund_void_and_settle_use_pay_api_endpoints(): void
    {
        Http::fake([
            'https://pay.solidgate.com/api/v1/status' => Http::response(['order' => ['status' => 'settle_ok']], 200),
            'https://pay.solidgate.com/api/v1/refund' => Http::response(['order' => ['status' => 'refunded']], 200),
            'https://pay.solidgate.com/api/v1/void' => Http::response(['order' => ['status' => 'void_ok']], 200),
            'https://pay.solidgate.com/api/v1/settle' => Http::response(['order' => ['status' => 'settle_ok']], 200),
        ]);

        $manager = new SolidGateManager($this->solidgateConfig());

        $this->assertTrue($manager->status(['order_id' => 'order-123'])->isSuccessful());
        $this->assertTrue($manager->refund(['order_id' => 'order-123', 'amount' => 500])->isSuccessful());
        $this->assertTrue($manager->void(['order_id' => 'order-123'])->isSuccessful());
        $this->assertTrue($manager->settle(['order_id' => 'order-123', 'amount' => 1000])->isSuccessful());

        Http::assertSent(fn ($request) => $request->url() === 'https://pay.solidgate.com/api/v1/status');
        Http::assertSent(fn ($request) => $request->url() === 'https://pay.solidgate.com/api/v1/refund');
        Http::assertSent(fn ($request) => $request->url() === 'https://pay.solidgate.com/api/v1/void');
        Http::assertSent(fn ($request) => $request->url() === 'https://pay.solidgate.com/api/v1/settle');
    }

    public function test_auth_uses_pay_api_auth_endpoint(): void
    {
        Http::fake([
            'https://pay.solidgate.com/api/v1/auth' => Http::response(['order' => ['status' => 'auth_ok']], 200),
        ]);

        $manager = new SolidGateManager($this->solidgateConfig());
        $response = $manager->auth([
            'amount' => 1000,
            'currency' => 'USD',
            'order_id' => 'order-123',
        ]);

        $this->assertTrue($response->isSuccessful());
        Http::assertSent(fn ($request) => $request->url() === 'https://pay.solidgate.com/api/v1/auth');
    }

    public function test_http_200_with_error_payload_is_not_successful(): void
    {
        Http::fake([
            'https://pay.solidgate.com/api/v1/charge' => Http::response([
                'error' => ['code' => '2.01', 'messages' => ['platform' => ['Platform is empty or invalid']]],
            ], 200),
        ]);

        $manager = new SolidGateManager($this->solidgateConfig());
        $response = $manager->send('charge', [
            'amount' => 1000,
            'currency' => 'USD',
            'order_id' => 'order-123',
        ]);

        $this->assertFalse($response->isSuccessful());
        $this->assertTrue($response->hasError());
        $this->assertSame('2.01', $response->get('error.code'));
    }

    public function test_get_request_uses_empty_body_signature(): void
    {
        Http::fake([
            'https://subscriptions.solidgate.com/api/v1/products*' => Http::response(['data' => []], 200),
        ]);

        $manager = new SolidGateManager($this->solidgateConfig());
        $response = $manager->getProductList(['filter' => ['status' => 'active']]);

        $this->assertTrue($response->isSuccessful());

        Http::assertSent(function ($request) {
            $expectedSignature = SignatureValidator::make('test-public', '', 'test-secret');

            return $request->method() === 'GET'
                && str_starts_with($request->url(), 'https://subscriptions.solidgate.com/api/v1/products')
                && $request->header('Signature')[0] === $expectedSignature;
        });
    }

    public function test_initialize_alternative_payment_applies_platform_default(): void
    {
        Http::fake([
            'https://gate.solidgate.com/api/v1/init-payment' => Http::response(['order' => []], 200),
        ]);

        $manager = new SolidGateManager($this->solidgateConfig());
        $manager->initializeAlternativePayment(['payment_method' => 'paypal-vault', 'order_id' => '123']);

        Http::assertSent(function ($request) {
            $payload = json_decode($request->body(), true);

            return $request->url() === 'https://gate.solidgate.com/api/v1/init-payment'
                && $payload['platform'] === 'WEB';
        });
    }

    public function test_recurring_alternative_payment_uses_gate_recurring_endpoint(): void
    {
        Http::fake([
            'https://gate.solidgate.com/api/v1/recurring' => Http::response(['order' => []], 200),
        ]);

        $manager = new SolidGateManager($this->solidgateConfig());
        $manager->recurringAlternativePayment(['order_id' => '123', 'amount' => 1000]);

        Http::assertSent(fn ($request) => $request->url() === 'https://gate.solidgate.com/api/v1/recurring');
    }

    public function test_routing_events_report_uses_reports_api(): void
    {
        Http::fake([
            'https://reports.solidgate.com/routing_events' => Http::response(['report_id' => 'RPT_123'], 200),
        ]);

        $manager = new SolidGateManager($this->solidgateConfig());
        $response = $manager->getRoutingEventsReport([
            'date_from' => '2025-08-15 11:00:00',
            'date_to' => '2025-08-18 11:00:00',
        ]);

        $this->assertTrue($response->isSuccessful());
        Http::assertSent(fn ($request) => $request->url() === 'https://reports.solidgate.com/routing_events');
    }

    public function test_webhook_controller_reads_event_type_from_header(): void
    {
        Event::fake([SolidGateWebhookReceived::class]);

        $request = Request::create(
            '/solidgate/webhook',
            'POST',
            [],
            [],
            [],
            [
                'HTTP_SOLIDGATE-EVENT-TYPE' => 'card_gate.order.updated',
                'HTTP_SOLIDGATE-EVENT-ID' => 'evt_123',
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode(['order' => ['order_id' => 'order-123', 'status' => 'settle_ok']])
        );

        $response = (new SolidGateWebhookController)->handle($request);

        $this->assertSame(200, $response->getStatusCode());
        Event::assertDispatched(SolidGateWebhookReceived::class, function (SolidGateWebhookReceived $event) {
            return $event->eventType === 'card_gate.order.updated'
                && $event->payload['order']['order_id'] === 'order-123';
        });
    }

    protected function solidgateConfig(): array
    {
        return [
            'public_key' => 'test-public',
            'secret_key' => 'test-secret',
            'default_platform' => 'WEB',
            'default_payment_type' => PaymentType::ONE_CLICK,
            'api' => [
                'base_url' => 'https://pay.solidgate.com/api/v1/',
                'subscriptions_url' => 'https://subscriptions.solidgate.com/api/v1/',
                'gate_url' => 'https://gate.solidgate.com/api/',
                'reports_url' => 'https://reports.solidgate.com/',
                'timeout' => 30,
                'verify_ssl' => true,
            ],
        ];
    }
}

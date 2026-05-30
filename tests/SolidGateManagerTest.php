<?php

namespace Lahiru\LaravelSolidGate\Tests;

use Illuminate\Support\Facades\Http;
use Lahiru\LaravelSolidGate\Services\SolidGateManager;
use Lahiru\LaravelSolidGate\Support\SignatureValidator;

class SolidGateManagerTest extends TestCase
{
    public function test_charge_sends_signed_json_body_to_pay_api(): void
    {
        Http::fake([
            'https://pay.solidgate.com/api/v1/charge' => Http::response(['order' => ['status' => 'processing']], 200),
        ]);

        $manager = new SolidGateManager($this->solidgateConfig());
        $attributes = ['amount' => 1000, 'currency' => 'USD', 'order_id' => 'order-123'];

        $response = $manager->charge($attributes);

        $this->assertTrue($response->isSuccessful());
        $this->assertSame('processing', $response->get('order.status'));

        Http::assertSent(function ($request) use ($attributes) {
            $body = json_encode($attributes);
            $expectedSignature = SignatureValidator::make('test-public', $body, 'test-secret');

            return $request->url() === 'https://pay.solidgate.com/api/v1/charge'
                && $request->method() === 'POST'
                && $request->body() === $body
                && $request->header('Merchant')[0] === 'test-public'
                && $request->header('Signature')[0] === $expectedSignature;
        });
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

    public function test_initialize_alternative_payment_uses_init_payment_endpoint(): void
    {
        Http::fake([
            'https://pay.solidgate.com/api/v1/init-payment' => Http::response(['order' => []], 200),
        ]);

        $manager = new SolidGateManager($this->solidgateConfig());
        $manager->initializeAlternativePayment(['payment_method' => 'paypal-vault', 'order_id' => '123']);

        Http::assertSent(fn ($request) => $request->url() === 'https://pay.solidgate.com/api/v1/init-payment');
    }

    protected function solidgateConfig(): array
    {
        return [
            'public_key' => 'test-public',
            'secret_key' => 'test-secret',
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

<?php

namespace Lahiru\LaravelSolidGate\Tests;

use Lahiru\LaravelSolidGate\Responses\SolidGateResponse;

class SolidGateResponseTest extends TestCase
{
    public function test_is_successful_when_http_ok_and_no_error_payload(): void
    {
        $response = new SolidGateResponse(['order' => ['status' => 'processing']], 200);

        $this->assertTrue($response->isSuccessful());
        $this->assertFalse($response->hasError());
    }

    public function test_is_not_successful_when_http_ok_but_error_payload_present(): void
    {
        $response = new SolidGateResponse([
            'error' => [
                'code' => '2.01',
                'messages' => ['payment_type' => ['Invalid value of payment_type']],
            ],
        ], 200);

        $this->assertFalse($response->isSuccessful());
        $this->assertTrue($response->hasError());
        $this->assertSame('2.01', $response->getError()['code']);
        $this->assertSame(
            'payment_type: Invalid value of payment_type',
            $response->getErrorMessage()
        );
    }
}

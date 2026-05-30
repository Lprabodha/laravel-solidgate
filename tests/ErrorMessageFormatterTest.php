<?php

namespace Lahiru\LaravelSolidGate\Tests;

use Lahiru\LaravelSolidGate\Support\ErrorMessageFormatter;

class ErrorMessageFormatterTest extends TestCase
{
    public function test_flattens_nested_field_messages(): void
    {
        $message = ErrorMessageFormatter::flatten([
            'payment_type' => ['Invalid value of payment_type'],
            'amount' => ['Amount is required'],
        ]);

        $this->assertSame(
            'payment_type: Invalid value of payment_type, amount: Amount is required',
            $message
        );
    }

    public function test_from_response_uses_error_messages(): void
    {
        $message = ErrorMessageFormatter::fromResponse([
            'error' => [
                'code' => '2.01',
                'messages' => ['payment_type' => ['Invalid value of payment_type']],
            ],
        ]);

        $this->assertSame('payment_type: Invalid value of payment_type', $message);
    }
}

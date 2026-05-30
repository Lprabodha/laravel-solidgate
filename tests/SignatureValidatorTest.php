<?php

namespace Lahiru\LaravelSolidGate\Tests;

use Lahiru\LaravelSolidGate\Support\SignatureValidator;

class SignatureValidatorTest extends TestCase
{
    public function test_it_generates_signature_using_official_solidgate_test_vector(): void
    {
        $publicKey = 'api_pk_8f8a8k8e8k8e8y8';
        $jsonString = '{"amount": "100", "currency": "USD"}';
        $secretKey = 'api_sk_8f8a8k8e8k8e8y8';
        $expected = 'MjFkZGE3ZTZjODc0YjY5YTczOTlmOTBlYjk0MDY1NThiODJiZmE3ZTgxOGJjMWUxYjNkNTFjMDNjZmUzOGRlMTBhZGEzMmYxMGY3NTBlOTBlMGZkNDUwZTRiNmI5YTBiYTVmZWM5NzcxMjU3OWM0MGU5Mzg1NTljOTE1NTVlNzA=';

        $signature = SignatureValidator::make($publicKey, $jsonString, $secretKey);

        $this->assertSame($expected, $signature);
    }

    public function test_it_generates_get_request_signature_with_empty_body(): void
    {
        $publicKey = 'api_pk_8f8a8k8e8k8e8y8';
        $secretKey = 'api_sk_8f8a8k8e8k8e8y8';
        $expected = 'MmQwNDExNjQwOWU5ZDdlMjliMWY0MWM0YjJlNzkwYjU2M2Y1ZDk5MjY3MWJlMzQwM2M4YzM3N2RiZTdhNGZmZDdhMDkyYzUyMjZmODlkN2RkYmM4NjRlODA3M2Q5ZDY4MWYxMDZmNTQ0MjI5ZTQyNTM0ZTg4NjkyNmEzMWJjMjI=';

        $signature = SignatureValidator::make($publicKey, '', $secretKey);

        $this->assertSame($expected, $signature);
    }

    public function test_it_validates_matching_signatures(): void
    {
        $publicKey = 'test-public';
        $payload = '{"order_id":"123"}';
        $secretKey = 'test-secret';
        $signature = SignatureValidator::make($publicKey, $payload, $secretKey);

        $this->assertTrue(
            SignatureValidator::isValid($publicKey, $payload, $secretKey, $signature)
        );
    }

    public function test_it_rejects_invalid_signatures(): void
    {
        $this->assertFalse(
            SignatureValidator::isValid('test-public', '{}', 'test-secret', 'invalid-signature')
        );
    }
}

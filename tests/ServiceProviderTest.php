<?php

namespace Lahiru\LaravelSolidGate\Tests;

use Lahiru\LaravelSolidGate\Contracts\SolidGateClientInterface;
use Lahiru\LaravelSolidGate\Facades\SolidGate;

class ServiceProviderTest extends TestCase
{
    public function test_it_registers_solidgate_client_binding(): void
    {
        $client = $this->app->make(SolidGateClientInterface::class);

        $this->assertInstanceOf(SolidGateClientInterface::class, $client);
    }

    public function test_facade_resolves_to_client(): void
    {
        $this->assertInstanceOf(SolidGateClientInterface::class, SolidGate::getFacadeRoot());
    }
}

<?php

namespace Lahiru\LaravelSolidGate\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Lahiru\LaravelSolidGate\LaravelSolidGateServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            LaravelSolidGateServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('solidgate.public_key', 'test-public');
        $app['config']->set('solidgate.secret_key', 'test-secret');
        $app['config']->set('solidgate.api.base_url', 'https://pay.solidgate.com/api/v1/');
        $app['config']->set('solidgate.api.subscriptions_url', 'https://subscriptions.solidgate.com/api/v1/');
        $app['config']->set('solidgate.api.gate_url', 'https://gate.solidgate.com/api/');
        $app['config']->set('solidgate.api.reports_url', 'https://reports.solidgate.com/');
    }
}
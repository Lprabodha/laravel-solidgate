<?php

namespace Lahiru\LaravelSolidGate;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Lahiru\LaravelSolidGate\Contracts\SolidGateClientInterface;
use Lahiru\LaravelSolidGate\Http\Controllers\SolidGateWebhookController;
use Lahiru\LaravelSolidGate\Http\Middleware\VerifySolidGateSignature;
use Lahiru\LaravelSolidGate\Services\SolidGateManager;

/**
 * Laravel SolidGate Service Provider.
 */
class LaravelSolidGateServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/solidgate.php', 'solidgate');

        $this->app->singleton(SolidGateClientInterface::class, function ($app) {
            return new SolidGateManager($app['config']->get('solidgate', []));
        });

        $this->app->singleton('solidgate', function ($app) {
            return $app->make(SolidGateClientInterface::class);
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/solidgate.php' => config_path('solidgate.php'),
        ], 'solidgate-config');

        // Register middleware alias
        if ($this->app->bound(Router::class)) {
            $router = $this->app->make(Router::class);
            $router->aliasMiddleware('solidgate.webhook', VerifySolidGateSignature::class);
        }

        // Register webhook route if enabled
        if (config('solidgate.webhook.enabled', false)) {
            $this->loadRoutes();
        }
    }

    /**
     * Load webhook routes.
     */
    protected function loadRoutes(): void
    {
        if (!$this->app->routesAreCached()) {
            $middleware = config('solidgate.webhook.middleware', 'solidgate.webhook');

            $this->app['router']->post(
                config('solidgate.webhook.path', 'solidgate/webhook'),
                [SolidGateWebhookController::class, 'handle']
            )->middleware(is_array($middleware) ? $middleware : [$middleware]);
        }
    }
}
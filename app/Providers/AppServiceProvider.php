<?php

/**
 * Service provider: application services registration.
 *
 * Registers application-wide bindings and bootstrapping logic used by the
 * service container and framework integration.
 *
 * PHP 8.1+
 *
 * @package   App\Providers
 */

/**
 * Application service provider.
 *
 * Registers and boots application-wide services.
 *
 * PHP 8.1+
 *
 * @package   App\Providers
 */

namespace App\Providers;

use App\Services\Integrations\ERPConnector;
use App\Services\Integrations\FiscalProvider;
use App\Services\Integrations\PaymentGateway;
use App\Services\Integrations\Stubs\StubERPConnector;
use App\Services\Integrations\Stubs\StubFiscalProvider;
use App\Services\Integrations\Stubs\StubPaymentGateway;
use Illuminate\Support\ServiceProvider;

/**
 * Registers and boots application services.
 *
 * Binds application services and performs framework integration at boot.
 *
 * @package   App\Providers
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->bind(PaymentGateway::class, StubPaymentGateway::class);
        $this->app->bind(FiscalProvider::class, StubFiscalProvider::class);
        $this->app->bind(ERPConnector::class, StubERPConnector::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        \Illuminate\Http\Resources\Json\JsonResource::withoutWrapping();

        // Load migrations from organized subdirectories
        // Swift-auth is loaded by vendor/equidna/swift-auth/src/Providers/SwiftAuthServiceProvider.php
        // We only load our custom swift-auth migrations here
        $this->loadMigrationsFrom([
            database_path('migrations/swift-auth'),
            database_path('migrations/pos'),
            database_path('migrations/bird-flock'),
        ]);
    }
}

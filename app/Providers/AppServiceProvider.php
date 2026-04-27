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

use App\Services\Integrations\BarcodeScanner;
use App\Services\Integrations\CashDrawer;
use App\Services\Integrations\FiscalProvider;
use App\Services\Integrations\PaymentGateway;
use App\Services\Integrations\ReceiptPrinter;
use App\Services\Integrations\ERPConnector;
use App\Services\Integrations\Mocks\MockBarcodeScanner;
use App\Services\Integrations\Mocks\MockCashDrawer;
use App\Services\Integrations\Mocks\MockFiscalProvider;
use App\Services\Integrations\Mocks\MockPaymentGateway;
use App\Services\Integrations\Mocks\MockReceiptPrinter;
use App\Services\Integrations\Stubs\StubERPConnector;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

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
        $this->app->bind(PaymentGateway::class, fn () => $this->integrationDriver(
            'payments.driver',
            ['mock' => MockPaymentGateway::class]
        ));
        $this->app->bind(FiscalProvider::class, fn () => $this->integrationDriver(
            'fiscal.driver',
            ['mock' => MockFiscalProvider::class]
        ));
        $this->app->bind(ReceiptPrinter::class, fn () => $this->integrationDriver(
            'receipt_printer.driver',
            ['mock' => MockReceiptPrinter::class]
        ));
        $this->app->bind(CashDrawer::class, fn () => $this->integrationDriver(
            'cash_drawer.driver',
            ['mock' => MockCashDrawer::class]
        ));
        $this->app->bind(BarcodeScanner::class, fn () => $this->integrationDriver(
            'barcode_scanner.driver',
            ['mock' => MockBarcodeScanner::class]
        ));
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

    private function integrationDriver(string $key, array $drivers): object
    {
        $driver = (string) config('pos_integrations.' . $key, 'mock');

        if (!array_key_exists($driver, $drivers)) {
            throw new RuntimeException("Unsupported POS integration driver [{$driver}] for [{$key}].");
        }

        return $this->app->make($drivers[$driver]);
    }
}

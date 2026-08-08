<?php

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
use App\Http\Middleware\E2eCaronteAuthentication;

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
            ['mock' => MockPaymentGateway::class],
            PaymentGateway::class
        ));
        $this->app->bind(FiscalProvider::class, fn () => $this->integrationDriver(
            'fiscal.driver',
            ['mock' => MockFiscalProvider::class],
            FiscalProvider::class
        ));
        $this->app->bind(ReceiptPrinter::class, fn () => $this->integrationDriver(
            'receipt_printer.driver',
            ['mock' => MockReceiptPrinter::class],
            ReceiptPrinter::class
        ));
        $this->app->bind(CashDrawer::class, fn () => $this->integrationDriver(
            'cash_drawer.driver',
            ['mock' => MockCashDrawer::class],
            CashDrawer::class
        ));
        $this->app->bind(BarcodeScanner::class, fn () => $this->integrationDriver(
            'barcode_scanner.driver',
            ['mock' => MockBarcodeScanner::class],
            BarcodeScanner::class
        ));
        $this->app->bind(ERPConnector::class, fn () => $this->integrationDriver(
            'erp.driver',
            ['stub' => StubERPConnector::class],
            ERPConnector::class
        ));
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        \Illuminate\Http\Resources\Json\JsonResource::withoutWrapping();

        $this->loadMigrationsFrom([
            database_path('migrations/pos'),
            database_path('migrations/bird-flock'),
        ]);

        if ($this->app->environment('testing') && config('app.e2e_enabled')) {
            $router = $this->app->make('router');
            $router->aliasMiddleware('caronte.session', E2eCaronteAuthentication::class);
            $router->aliasMiddleware('caronte.application', E2eCaronteAuthentication::class);
        }

        // Laravel defaults to "production" before an environment file exists.
        // Enforce only when production was explicitly selected so Composer and
        // first-install Artisan commands can bootstrap safely.
        if (config('pos_integrations.enforce_production_drivers')) {
            $this->assertProductionIntegrationsAreSafe();
        }
    }

    private function integrationDriver(string $key, array $drivers, string $contract): object
    {
        $driver = (string) config('pos_integrations.' . $key, 'mock');

        $implementation = $drivers[$driver] ?? $driver;

        if (!class_exists($implementation) || !is_a($implementation, $contract, true)) {
            throw new RuntimeException("Unsupported POS integration driver [{$driver}] for [{$key}].");
        }

        return $this->app->make($implementation);
    }

    private function assertProductionIntegrationsAreSafe(): void
    {
        $drivers = [
            'payments.driver',
            'fiscal.driver',
            'receipt_printer.driver',
            'cash_drawer.driver',
            'barcode_scanner.driver',
            'erp.driver',
        ];

        $unsafe = array_values(array_filter($drivers, function (string $key): bool {
            $driver = strtolower((string) config('pos_integrations.' . $key, ''));

            return $driver === '' || in_array($driver, ['mock', 'stub', 'fake'], true);
        }));

        if ($unsafe !== []) {
            throw new RuntimeException(
                'Production POS integrations must use real drivers. Unsafe configuration: ' . implode(', ', $unsafe)
            );
        }
    }
}

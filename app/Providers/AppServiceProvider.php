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
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        \Illuminate\Http\Resources\Json\JsonResource::withoutWrapping();
    }
}

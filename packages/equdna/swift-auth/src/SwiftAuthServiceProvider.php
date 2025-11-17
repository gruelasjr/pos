<?php

namespace Equdna\SwiftAuth;

use Equdna\SwiftAuth\Guards\SwiftTokenGuard;
use Equdna\SwiftAuth\Services\SwiftAuthManager;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class SwiftAuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SwiftAuthManager::class, fn () => new SwiftAuthManager());
    }

    public function boot(): void
    {
        Auth::extend('swift', function ($app, $name, array $config) {
            $provider = Auth::createUserProvider($config['provider'] ?? null);

            return new SwiftTokenGuard(
                $provider,
                $app['request']
            );
        });
    }
}

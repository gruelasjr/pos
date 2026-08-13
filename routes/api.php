<?php

/**
 * API routes loader.
 *
 * Registers top-level API routes for the application.
 *
 * PHP 8.1+
 *
 * @package   Routes
 */

/**
 * API routes loader. Splits API v1 into per-area route files.
 *
 * PHP 8.1+
 *
 * @package   Routes
 */

use App\Http\Middleware\UseCaronteSessionToken;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    require __DIR__ . '/api/v1/customers_public.php';

    // These endpoints are consumed by the same-origin Inertia browser client.
    // The OIDC SDK stores the ID token in Laravel's session, so the web
    // middleware must run here to decrypt the cookie and start that session.
    // Public and application-token endpoints below remain stateless.
    Route::middleware([
        EncryptCookies::class,
        AddQueuedCookiesToResponse::class,
        StartSession::class,
        ValidateCsrfToken::class,
        UseCaronteSessionToken::class,
        'caronte.session',
        'caronte.user',
    ])->group(function () {
        require __DIR__ . '/api/v1/warehouses.php';
        require __DIR__ . '/api/v1/product_types.php';
        require __DIR__ . '/api/v1/product_taxonomy.php';
        require __DIR__ . '/api/v1/products.php';
        require __DIR__ . '/api/v1/inventory.php';
        require __DIR__ . '/api/v1/skus.php';
        require __DIR__ . '/api/v1/carts.php';
        require __DIR__ . '/api/v1/sales.php';
        require __DIR__ . '/api/v1/returns.php';
        require __DIR__ . '/api/v1/cash_sessions.php';
        require __DIR__ . '/api/v1/customers.php';
        require __DIR__ . '/api/v1/promotions.php';
        require __DIR__ . '/api/v1/loyalty.php';
        require __DIR__ . '/api/v1/coupons.php';
        require __DIR__ . '/api/v1/reports.php';
    });

    Route::middleware('caronte.application:tenant_required')->group(function () {
        require __DIR__ . '/api/v1/hardware.php';
        require __DIR__ . '/api/v1/outbox.php';
    });
});

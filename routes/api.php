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

use Illuminate\Support\Facades\Route;

Route::prefix('api/v1')->group(function () {
    Route::middleware('SwiftAuth.RequireAuthentication')->group(function () {
        require __DIR__ . '/api/v1/warehouses.php';
        require __DIR__ . '/api/v1/product_types.php';
        require __DIR__ . '/api/v1/products.php';
        require __DIR__ . '/api/v1/inventory.php';
        require __DIR__ . '/api/v1/skus.php';
        require __DIR__ . '/api/v1/carts.php';
        require __DIR__ . '/api/v1/sales.php';
        require __DIR__ . '/api/v1/customers.php';
        require __DIR__ . '/api/v1/reports.php';
    });
});

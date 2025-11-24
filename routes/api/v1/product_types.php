<?php

/**
 * API v1 routes for product types.
 *
 * CRUD endpoints for product type entities.
 *
 * PHP 8.1+
 *
 * @package   Routes\API\V1
 */

/**
 * API v1 - Product type routes.
 *
 * PHP 8.1+
 *
 * @package   Routes\API\V1
 */

use App\Http\Controllers\API\V1\ProductTypeController;
use Illuminate\Support\Facades\Route;

Route::get('product-types', [ProductTypeController::class, 'index']);
Route::post('product-types', [ProductTypeController::class, 'store']);
Route::patch('product-types/{productType}', [ProductTypeController::class, 'update']);

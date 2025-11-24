<?php

/**
 * API routes: carts (v1)
 *
 * Route definitions for cart operations used by the POS API v1.
 *
 * PHP 8.1+
 *
 * @package   Routes\API\V1
 */

/**
 * API v1 routes for carts.
 *
 * Endpoints to manage shopping carts and checkout operations.
 *
 * PHP 8.1+
 *
 * @package   Routes\API\V1
 */

/**
 * API v1 - Cart routes.
 *
 * PHP 8.1+
 *
 * @package   Routes\API\V1
 */

use App\Http\Controllers\API\V1\CartController;
use Illuminate\Support\Facades\Route;

Route::get('carts', [CartController::class, 'index']);
Route::post('carts', [CartController::class, 'store']);
Route::patch('carts/{cart}', [CartController::class, 'updateCart']);
Route::post('carts/{cart}/items', [CartController::class, 'addItem']);
Route::patch('carts/{cart}/items/{itemId}', [CartController::class, 'updateItem']);
Route::delete('carts/{cart}/items/{itemId}', [CartController::class, 'deleteItem']);
Route::post('carts/{cart}/checkout', [CartController::class, 'checkout']);

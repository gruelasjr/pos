<?php

/**
 * API v1 routes for sales.
 *
 * Registers endpoints for listing, showing and managing sales.
 *
 * PHP 8.1+
 *
 * @package   Routes\API\V1
 */

/**
 * API v1 - Sales routes.
 *
 * PHP 8.1+
 *
 * @package   Routes\API\V1
 */

use App\Http\Controllers\API\V1\SaleController;
use Illuminate\Support\Facades\Route;

Route::get('sales', [SaleController::class, 'index']);
Route::get('sales/{sale}', [SaleController::class, 'show']);
Route::post('sales/{sale}/receipt', [SaleController::class, 'sendReceipt']);

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
Route::post('sales/{sale}/receipt', [SaleController::class, 'sendReceipt'])
    ->middleware(['role:admin,vendedor', 'throttle:receipt-send']);
Route::post('sales/{sale}/print', [SaleController::class, 'printReceipt'])->middleware('role:admin,vendedor');
Route::post('sales/{sale}/fiscal-document', [SaleController::class, 'issueFiscalDocument'])->middleware('role:admin');

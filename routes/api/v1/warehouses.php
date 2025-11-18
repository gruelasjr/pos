<?php

/**
 * API v1 - Warehouse routes.
 *
 * PHP 8.1+
 *
 * @package   Routes\API\V1
 */

use App\Http\Controllers\API\V1\WarehouseController;
use Illuminate\Support\Facades\Route;

Route::get('warehouses', [WarehouseController::class, 'index']);
Route::post('warehouses', [WarehouseController::class, 'store']);
Route::patch('warehouses/{warehouse}', [WarehouseController::class, 'update']);

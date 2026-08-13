<?php

/**
 * API v1 routes for inventory.
 *
 * Endpoints to list and adjust inventory.
 *
 * PHP 8.1+
 *
 * @package   Routes\API\V1
 */

/**
 * API v1 - Inventory routes.
 *
 * PHP 8.1+
 *
 * @package   Routes\API\V1
 */

use App\Http\Controllers\API\V1\InventoryController;
use Illuminate\Support\Facades\Route;

Route::get('inventory', [InventoryController::class, 'index']);
Route::patch('inventory/adjust', [InventoryController::class, 'adjust'])->middleware(['role:admin', 'throttle:admin-mutations']);
Route::patch('inventory/{inventory}', [InventoryController::class, 'update'])->middleware(['role:admin', 'throttle:admin-mutations']);

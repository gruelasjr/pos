<?php

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
Route::patch('inventory/adjust', [InventoryController::class, 'adjust']);

<?php

/**
 * API v1 routes for customers.
 *
 * Customer listing, creation and registration endpoints.
 *
 * PHP 8.1+
 *
 * @package   Routes\API\V1
 */

/**
 * API v1 - Customer routes.
 *
 * PHP 8.1+
 *
 * @package   Routes\API\V1
 */

use App\Http\Controllers\API\V1\CustomerController;
use Illuminate\Support\Facades\Route;

Route::get('customers', [CustomerController::class, 'index']);
Route::post('customers', [CustomerController::class, 'store'])->middleware(['role:admin,vendedor', 'throttle:admin-mutations']);
Route::patch('customers/{customer}', [CustomerController::class, 'update'])->middleware(['role:admin,vendedor', 'throttle:admin-mutations']);

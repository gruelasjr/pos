<?php

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
Route::post('customers', [CustomerController::class, 'store']);
Route::patch('customers/{customer}', [CustomerController::class, 'update']);
Route::post('customers/register', [CustomerController::class, 'register']);

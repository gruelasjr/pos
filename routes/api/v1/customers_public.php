<?php

/**
 * API v1 public routes for customers.
 *
 * Customer self-registration endpoint exposed without authentication.
 *
 * PHP 8.1+
 *
 * @package   Routes\API\V1
 */

use App\Http\Controllers\API\V1\CustomerController;
use Illuminate\Support\Facades\Route;

Route::post('customers/register', [CustomerController::class, 'register'])->middleware('throttle:public-registration');

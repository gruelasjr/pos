<?php

/**
 * API v1 routes for SKU reservation.
 *
 * Routes to reserve and query SKU ranges.
 *
 * PHP 8.1+
 *
 * @package   Routes\API\V1
 */

/**
 * API v1 - SKU routes.
 *
 * PHP 8.1+
 *
 * @package   Routes\API\V1
 */

use App\Http\Controllers\API\V1\SkuController;
use Illuminate\Support\Facades\Route;

Route::post('skus/reserve', [SkuController::class, 'reserve']);

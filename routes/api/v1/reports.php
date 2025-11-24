<?php

/**
 * API v1 routes for reports.
 *
 * Endpoints for daily/weekly/monthly sales reports.
 *
 * PHP 8.1+
 *
 * @package   Routes\API\V1
 */

/**
 * API v1 - Reports routes.
 *
 * PHP 8.1+
 *
 * @package   Routes\API\V1
 */

use App\Http\Controllers\API\V1\ReportController;
use Illuminate\Support\Facades\Route;

Route::get('reports/daily', [ReportController::class, 'daily']);
Route::get('reports/weekly', [ReportController::class, 'weekly']);
Route::get('reports/monthly', [ReportController::class, 'monthly']);
Route::get('reports/by-seller', [ReportController::class, 'bySeller']);

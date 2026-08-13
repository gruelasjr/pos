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
Route::get('reports/daily/export', [ReportController::class, 'dailyExport'])->middleware('role:admin,auditor');
Route::get('reports/weekly', [ReportController::class, 'weekly']);
Route::get('reports/monthly', [ReportController::class, 'monthly']);
Route::get('reports/by-seller', [ReportController::class, 'bySeller']);
Route::get('reports/by-seller/export', [ReportController::class, 'bySellerExport'])->middleware('role:admin,auditor');
Route::get('reports/overview', [ReportController::class, 'overview']);
Route::get('reports/best-sellers', [ReportController::class, 'bestSellers']);
Route::get('reports/export', [ReportController::class, 'export'])->middleware('role:admin,auditor');

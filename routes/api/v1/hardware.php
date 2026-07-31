<?php

use App\Http\Controllers\API\V1\HardwareController;
use Illuminate\Support\Facades\Route;

Route::get('hardware/status', [HardwareController::class, 'status']);
Route::post('hardware/barcode/parse', [HardwareController::class, 'parseBarcode']);
Route::post('hardware/cash-drawer/open', [HardwareController::class, 'openCashDrawer']);

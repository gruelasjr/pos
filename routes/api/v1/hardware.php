<?php

use App\Http\Controllers\API\V1\HardwareController;
use Illuminate\Support\Facades\Route;

Route::get('hardware/status', [HardwareController::class, 'status'])->middleware('role:admin,vendedor');
Route::post('hardware/barcode/parse', [HardwareController::class, 'parseBarcode'])->middleware('role:admin,vendedor');
Route::post('hardware/cash-drawer/open', [HardwareController::class, 'openCashDrawer'])->middleware('role:admin');

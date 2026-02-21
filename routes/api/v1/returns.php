<?php

use App\Http\Controllers\API\V1\ReturnController;
use Illuminate\Support\Facades\Route;

Route::get('returns', [ReturnController::class, 'index'])->middleware('role:admin,auditor');
Route::get('returns/{return}', [ReturnController::class, 'show'])->middleware('role:admin,auditor');
Route::post('sales/{sale}/returns', [ReturnController::class, 'store'])->middleware('role:admin,vendedor');

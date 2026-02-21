<?php

use App\Http\Controllers\API\V1\CashSessionController;
use Illuminate\Support\Facades\Route;

Route::get('cash-sessions', [CashSessionController::class, 'index'])->middleware('role:admin,vendedor,auditor');
Route::post('cash-sessions/open', [CashSessionController::class, 'open'])->middleware('role:admin,vendedor');
Route::post('cash-sessions/{cashSession}/close', [CashSessionController::class, 'close'])->middleware('role:admin,vendedor');

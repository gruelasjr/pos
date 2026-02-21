<?php

use App\Http\Controllers\API\V1\LoyaltyController;
use Illuminate\Support\Facades\Route;

Route::get('loyalty/customers/{customer}', [LoyaltyController::class, 'showByCustomer'])->middleware('role:admin,vendedor,auditor');
Route::post('loyalty/redeem', [LoyaltyController::class, 'redeem'])->middleware('role:admin,vendedor');

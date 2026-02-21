<?php

use App\Http\Controllers\API\V1\CouponController;
use Illuminate\Support\Facades\Route;

Route::get('coupons', [CouponController::class, 'index'])->middleware('role:admin,vendedor,auditor');
Route::post('coupons', [CouponController::class, 'store'])->middleware('role:admin');

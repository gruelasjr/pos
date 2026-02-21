<?php

use App\Http\Controllers\API\V1\PromotionController;
use Illuminate\Support\Facades\Route;

Route::get('promotions', [PromotionController::class, 'index'])->middleware('role:admin,vendedor,auditor');
Route::post('promotions', [PromotionController::class, 'store'])->middleware('role:admin');
Route::patch('promotions/{promotion}', [PromotionController::class, 'update'])->middleware('role:admin');

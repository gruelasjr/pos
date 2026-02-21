<?php

use App\Http\Controllers\API\V1\OutboxController;
use Illuminate\Support\Facades\Route;

Route::get('outbox', [OutboxController::class, 'index'])->middleware('role:admin,auditor');
Route::post('outbox/{outboxMessage}/retry', [OutboxController::class, 'retry'])->middleware('role:admin');

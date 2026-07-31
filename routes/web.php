<?php

use App\Http\Controllers\Health\ReadinessController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/ready', ReadinessController::class)->name('ready');
Route::get('/login', fn () => redirect((string) config('caronte.routes.login_url')))->name('login');

Route::middleware(['caronte.session', 'caronte.user'])->group(function (): void {
    Route::get('/', fn () => Inertia::render('Dashboard/Index'));
    Route::get('/catalogo/almacenes', fn () => Inertia::render('Catalog/Warehouses'));
    Route::get('/catalogo/productos', fn () => Inertia::render('Catalog/Products'));
    Route::get('/pos', fn () => Inertia::render('POS/Carts'));
    Route::get('/clientes', fn () => Inertia::render('Customers/Index'));
    Route::get('/registro-cliente', fn () => Inertia::render('Customers/Register'));
    Route::get('/reportes', fn () => Inertia::render('Reports/Index'));
});

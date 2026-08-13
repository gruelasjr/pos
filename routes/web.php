<?php

use App\Http\Controllers\Health\ReadinessController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/ready', ReadinessController::class)->name('ready');
Route::get('/login', fn () => redirect((string) config('caronte.routes.login_url')))->name('login');

Route::middleware(['caronte.session', 'caronte.user'])->group(function (): void {
    Route::get('/', fn () => Inertia::render('Dashboard/Index'));
    Route::get('/catalogo/almacenes', fn () => Inertia::render('Catalog/Warehouses'));
    Route::get('/catalogo/productos', fn () => Inertia::render('Catalog/Products'));
    Route::get('/catalogo/inventario', fn () => Inertia::render('Catalog/Inventory'));
    Route::get('/catalogo/skus', fn () => Inertia::render('Catalog/SkuRanges'));
    Route::get('/pos', fn () => Inertia::render('POS/Carts'));
    Route::get('/clientes', fn () => Inertia::render('Customers/Index'));
    Route::get('/reportes', fn () => Inertia::render('Reports/Index'));
    Route::get('/ventas', fn () => Inertia::render('Sales/Index'));
});

Route::get('/r/{token}', fn (string $token) => Inertia::render('Customers/Register', ['registrationToken' => $token]));
Route::get('/registro-cliente', function (Request $request) {
    $token = $request->query('token');

    return $token
        ? redirect('/r/' . rawurlencode((string) $token))
        : Inertia::render('Customers/Register');
});

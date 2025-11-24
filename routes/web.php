<?php

/**
 * Web routes loader.
 *
 * Registers web routes for the application.
 *
 * PHP 8.1+
 *
 * @package   Routes
 */

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => Inertia::render('Dashboard/Index'));
Route::get('/login', fn () => Inertia::render('Auth/Login'));
Route::get('/catalogo/almacenes', fn () => Inertia::render('Catalog/Warehouses'));
Route::get('/catalogo/productos', fn () => Inertia::render('Catalog/Products'));
Route::get('/pos', fn () => Inertia::render('POS/Carts'));
Route::get('/clientes', fn () => Inertia::render('Customers/Index'));
Route::get('/reportes', fn () => Inertia::render('Reports/Index'));
Route::fallback(fn () => Inertia::render('Dashboard/Index'));

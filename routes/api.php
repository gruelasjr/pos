<?php

use App\Http\Controllers\API\V1\AuthController;
use App\Http\Controllers\API\V1\CartController;
use App\Http\Controllers\API\V1\CustomerController;
use App\Http\Controllers\API\V1\InventoryController;
use App\Http\Controllers\API\V1\ProductController;
use App\Http\Controllers\API\V1\ProductTypeController;
use App\Http\Controllers\API\V1\ReportController;
use App\Http\Controllers\API\V1\SaleController;
use App\Http\Controllers\API\V1\SkuController;
use App\Http\Controllers\API\V1\WarehouseController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1')->group(function () {
    Route::post('auth/login', [AuthController::class, 'login']);

    Route::middleware('auth:swift')->group(function () {
        Route::get('warehouses', [WarehouseController::class, 'index']);
        Route::post('warehouses', [WarehouseController::class, 'store']);
        Route::patch('warehouses/{warehouse}', [WarehouseController::class, 'update']);

        Route::get('product-types', [ProductTypeController::class, 'index']);
        Route::post('product-types', [ProductTypeController::class, 'store']);
        Route::patch('product-types/{productType}', [ProductTypeController::class, 'update']);

        Route::get('products', [ProductController::class, 'index']);
        Route::get('products/{product}', [ProductController::class, 'show']);
        Route::post('products', [ProductController::class, 'store']);
        Route::patch('products/{product}', [ProductController::class, 'update']);

        Route::get('inventory', [InventoryController::class, 'index']);
        Route::patch('inventory/adjust', [InventoryController::class, 'adjust']);

        Route::post('skus/reserve', [SkuController::class, 'reserve']);

        Route::get('carts', [CartController::class, 'index']);
        Route::post('carts', [CartController::class, 'store']);
        Route::patch('carts/{cart}', [CartController::class, 'updateCart']);
        Route::post('carts/{cart}/items', [CartController::class, 'addItem']);
        Route::patch('carts/{cart}/items/{itemId}', [CartController::class, 'updateItem']);
        Route::delete('carts/{cart}/items/{itemId}', [CartController::class, 'deleteItem']);
        Route::post('carts/{cart}/checkout', [CartController::class, 'checkout']);

        Route::get('sales', [SaleController::class, 'index']);
        Route::get('sales/{sale}', [SaleController::class, 'show']);
        Route::post('sales/{sale}/receipt', [SaleController::class, 'sendReceipt']);

        Route::get('customers', [CustomerController::class, 'index']);
        Route::post('customers', [CustomerController::class, 'store']);
        Route::patch('customers/{customer}', [CustomerController::class, 'update']);
        Route::post('customers/register', [CustomerController::class, 'register']);

        Route::get('reports/daily', [ReportController::class, 'daily']);
        Route::get('reports/weekly', [ReportController::class, 'weekly']);
        Route::get('reports/monthly', [ReportController::class, 'monthly']);
        Route::get('reports/by-seller', [ReportController::class, 'bySeller']);
    });
});

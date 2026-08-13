<?php

use App\Http\Controllers\API\V1\ProductTaxonomyController;
use Illuminate\Support\Facades\Route;

Route::get('product-tags', [ProductTaxonomyController::class, 'tags']);
Route::post('product-tags', [ProductTaxonomyController::class, 'storeTag'])->middleware(['role:admin', 'throttle:admin-mutations']);
Route::patch('product-tags/{productTag}', [ProductTaxonomyController::class, 'updateTag'])->middleware(['role:admin', 'throttle:admin-mutations']);
Route::delete('product-tags/{productTag}', [ProductTaxonomyController::class, 'deleteTag'])->middleware(['role:admin', 'throttle:admin-mutations']);
Route::get('product-metadata-definitions', [ProductTaxonomyController::class, 'metadata']);
Route::post('product-metadata-definitions', [ProductTaxonomyController::class, 'storeMetadata'])->middleware(['role:admin', 'throttle:admin-mutations']);
Route::patch('product-metadata-definitions/{definition}', [ProductTaxonomyController::class, 'updateMetadata'])->middleware(['role:admin', 'throttle:admin-mutations']);
Route::delete('product-metadata-definitions/{definition}', [ProductTaxonomyController::class, 'deleteMetadata'])->middleware(['role:admin', 'throttle:admin-mutations']);
Route::post('product-metadata-definitions/{definition}/coded-values', [ProductTaxonomyController::class, 'storeCodedValue'])->middleware(['role:admin', 'throttle:admin-mutations']);
Route::patch('product-metadata-coded-values/{codedValue}', [ProductTaxonomyController::class, 'updateCodedValue'])->middleware(['role:admin', 'throttle:admin-mutations']);

<?php

use App\Http\Controllers\Api\DishController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;

Route::prefix('products')->group(function (): void {
    Route::get('/', [ProductController::class, 'index']);
    Route::post('/', [ProductController::class, 'store']);
    Route::get('/{product}', [ProductController::class, 'show']);
    Route::put('/{product}', [ProductController::class, 'update']);
    Route::delete('/{product}', [ProductController::class, 'destroy']);
});

Route::prefix('dishes')->group(function (): void {
    Route::get('/', [DishController::class, 'index']);
    Route::post('/', [DishController::class, 'store']);
    Route::get('/{dish}', [DishController::class, 'show']);
    Route::put('/{dish}', [DishController::class, 'update']);
    Route::delete('/{dish}', [DishController::class, 'destroy']);
});

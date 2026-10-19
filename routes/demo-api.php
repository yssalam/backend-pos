<?php

use App\Http\Controllers\Api\DemoApiController;
use App\Http\Middleware\DemoAdmin;
use App\Http\Middleware\DemoApiMode;
use App\Http\Middleware\DemoSuperAdmin;
use Illuminate\Support\Facades\Route;

Route::post('/login', [DemoApiController::class, 'login']);
Route::middleware(DemoApiMode::class)->group(function () {
    Route::get('/profile', [DemoApiController::class, 'profile']);
    Route::post('/logout', [DemoApiController::class, 'logout']);
    Route::post('/sales', [DemoApiController::class, 'sale']);
    Route::middleware(DemoSuperAdmin::class)->prefix('admin')->group(function () {
        Route::get('/users', [DemoApiController::class, 'users']);
        Route::post('/users', [DemoApiController::class, 'createAdmin']);
        Route::put('/users/{id}', [DemoApiController::class, 'updateUser'])->whereNumber('id');
    });
    Route::get('/products', [DemoApiController::class, 'products']);
    Route::get('/products/{id}', [DemoApiController::class, 'product'])->whereNumber('id');
    Route::middleware(DemoAdmin::class)->group(function () {
        Route::post('/products', [DemoApiController::class, 'createProduct']);
        Route::put('/products/{id}', [DemoApiController::class, 'updateProduct'])->whereNumber('id');
        Route::delete('/products/{id}', [DemoApiController::class, 'deleteProduct'])->whereNumber('id');
        Route::post('/categories', [DemoApiController::class, 'createCategory']);
        Route::put('/categories/{id}', [DemoApiController::class, 'updateCategory'])->whereNumber('id');
        Route::delete('/categories/{id}', [DemoApiController::class, 'deleteCategory'])->whereNumber('id');
    });
    Route::get('/categories', [DemoApiController::class, 'categories']);
    Route::get('/categories/{id}', [DemoApiController::class, 'category'])->whereNumber('id');
    Route::post('/stock/in', [DemoApiController::class, 'stockIn']);
    Route::post('/stock/out', [DemoApiController::class, 'stockOut']);
    Route::post('/stock/adjustment', [DemoApiController::class, 'adjustment']);
    Route::get('/stock/movements', [DemoApiController::class, 'movements']);
    Route::get('/products/{id}/stock-movements', [DemoApiController::class, 'productMovements'])->whereNumber('id');
});


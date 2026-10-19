<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\StockController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SaleController;
if (config('demo.api') && !app()->runningUnitTests()) {
    require base_path('routes/demo-api.php');
    return;
}

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

//Auth


Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    // Profil dan logout
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Transaksi penjualan atomik dengan pengurangan stok dan stock movement.
    Route::post('/sales', [SaleController::class, 'store']);

    // User: super admin saja
    Route::middleware('super_admin')->group(function () {
        Route::post('/admin/users', [AuthController::class, 'storeAdmin']);
        Route::get('/admin/users', [AuthController::class, 'index']);
        Route::put('/admin/users/{user}', [AuthController::class, 'updateUser']);
    });

    // Produk: semua role boleh melihat
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{product}', [ProductController::class, 'show']);

    // Produk: admin dan super admin boleh mengelola
    Route::middleware('admin')->group(function () {
        Route::post('/products', [ProductController::class, 'store']);
        Route::put('/products/{product}', [ProductController::class, 'update']);
        Route::delete('/products/{product}', [ProductController::class, 'delete']);

        // Kategori: admin dan super admin boleh mengelola
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::put('/categories/{category}', [CategoryController::class, 'update']);
        Route::delete('/categories/{category}', [CategoryController::class, 'delete']);
    });

    // Kategori: semua role boleh melihat
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{category}', [CategoryController::class, 'show']);

    // Stok: semua role yang login boleh mengakses
    Route::post('/stock/in', [StockController::class, 'stockIn']);
    Route::post('/stock/out', [StockController::class, 'stockOut']);
    Route::post('/stock/adjustment', [StockController::class, 'adjustment']);
    Route::get('/stock/movements', [StockController::class, 'movements']);
    Route::get(
        '/products/{product}/stock-movements',
        [StockController::class, 'productMovements']
    );
});





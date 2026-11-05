<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\IngredientController;
use App\Http\Controllers\RecipeController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\StockAdjustmentController;
use App\Http\Controllers\SaleController;

// Halaman login untuk admin yang belum login
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])
        ->name('login');

    Route::post('/login', [AuthController::class, 'login'])
        ->name('login.process');
});

// Halaman yang hanya bisa diakses setelah login
Route::middleware('auth')->group(function () {

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])
        ->name('dashboard');

    // Logout
    Route::post('/logout', [AuthController::class, 'logout'])
        ->name('logout');

    // Kategori
    Route::resource('categories', CategoryController::class);

    // Produk menu
    Route::resource('products', ProductController::class);

    // Bahan baku
    Route::resource('ingredients', IngredientController::class);

    // Resep
    Route::resource('recipes', RecipeController::class);

    // Pembelian
    Route::resource('purchases', PurchaseController::class)
        ->only(['index', 'create', 'store']);

    // Penjualan
    Route::resource('sales', SaleController::class)
        ->only(['index', 'create', 'store']);

    // Riwayat pergerakan stok
    Route::get('/stock-movements', [StockMovementController::class, 'index'])
        ->name('stock-movements.index');

    // Penyesuaian stok
    Route::resource('stock-adjustments', StockAdjustmentController::class)
        ->only(['index', 'create', 'store']);
});

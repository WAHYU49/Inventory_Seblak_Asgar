<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\IngredientController;
use App\Http\Controllers\RecipeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PurchaseController;

Route::get('/', function () {
    return view('welcome');
});
Route::resource('categories', CategoryController::class);
Route::resource('products', ProductController::class);
Route::resource('ingredients', IngredientController::class);
Route::resource('recipes', RecipeController::class);
Route::resource('purchases', PurchaseController::class)
    ->only(['index', 'create', 'store']);
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

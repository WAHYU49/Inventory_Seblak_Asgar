<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Ingredient;
use App\Models\Recipe;

class DashboardController extends Controller
{
    public function index()
    {
        $totalProducts = Product::count();

        $totalIngredients = Ingredient::where('is_active', true)->count();

        $outOfStockIngredients = Ingredient::where('is_active', true)
            ->where('current_stock', '<=', 0)
            ->count();

        $lowStockIngredients = Ingredient::where('is_active', true)
            ->where('current_stock', '>', 0)
            ->whereColumn('current_stock', '<=', 'minimum_stock')
            ->count();

        $safeStockIngredients = Ingredient::where('is_active', true)
            ->whereColumn('current_stock', '>', 'minimum_stock')
            ->count();

        $totalRecipes = Recipe::where('is_active', true)->count();

        $lowStockList = Ingredient::where('is_active', true)
            ->whereColumn('current_stock', '<=', 'minimum_stock')
            ->orderBy('current_stock')
            ->get();

        return view('dashboard', compact(
            'totalProducts',
            'totalIngredients',
            'outOfStockIngredients',
            'lowStockIngredients',
            'safeStockIngredients',
            'totalRecipes',
            'lowStockList'
        ));
    }
}

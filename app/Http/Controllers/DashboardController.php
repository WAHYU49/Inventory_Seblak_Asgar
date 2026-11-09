<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\Product;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\Sale;
use App\Models\StockAdjustment;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
        ]);

        $totalProducts = Product::count();

        $stockSummary = Ingredient::where('is_active', true)
            ->selectRaw('COUNT(*) as total_ingredients')
            ->selectRaw('COALESCE(SUM(CASE WHEN current_stock <= 0 THEN 1 ELSE 0 END), 0) as out_of_stock')
            ->selectRaw('COALESCE(SUM(CASE WHEN current_stock > 0 AND current_stock <= minimum_stock THEN 1 ELSE 0 END), 0) as low_stock')
            ->selectRaw('COALESCE(SUM(CASE WHEN current_stock > minimum_stock THEN 1 ELSE 0 END), 0) as safe_stock')
            ->first();

        $totalIngredients = (int) $stockSummary->total_ingredients;
        $outOfStockIngredients = (int) $stockSummary->out_of_stock;
        $lowStockIngredients = (int) $stockSummary->low_stock;
        $safeStockIngredients = (int) $stockSummary->safe_stock;

        $totalRecipes = Recipe::where('is_active', true)->count();

        $lowStockList = Ingredient::where('is_active', true)
            ->where('current_stock', '>', 0)
            ->whereColumn('current_stock', '<=', 'minimum_stock')
            ->orderBy('current_stock')
            ->limit(10)
            ->get();

        $purchaseQuery = Purchase::query()
            ->when(
                $filters['start_date'] ?? null,
                fn($query, $date) => $query->whereDate('purchase_date', '>=', $date)
            )
            ->when(
                $filters['end_date'] ?? null,
                fn($query, $date) => $query->whereDate('purchase_date', '<=', $date)
            );
        $totalPurchases = (clone $purchaseQuery)->count();
        $recentPurchases = (clone $purchaseQuery)
            ->with('details.ingredient')
            ->latest()
            ->limit(10)
            ->get();

        $saleQuery = Sale::query()
            ->when(
                $filters['start_date'] ?? null,
                fn($query, $date) => $query->whereDate('sale_date', '>=', $date)
            )
            ->when(
                $filters['end_date'] ?? null,
                fn($query, $date) => $query->whereDate('sale_date', '<=', $date)
            );
        $totalSales = (clone $saleQuery)->count();
        $recentSales = (clone $saleQuery)
            ->with('details.product')
            ->latest()
            ->limit(10)
            ->get();

        $adjustmentQuery = StockAdjustment::query()
            ->when(
                $filters['start_date'] ?? null,
                fn($query, $date) => $query->whereDate('adjustment_date', '>=', $date)
            )
            ->when(
                $filters['end_date'] ?? null,
                fn($query, $date) => $query->whereDate('adjustment_date', '<=', $date)
            );
        $totalAdjustments = (clone $adjustmentQuery)->count();
        $recentAdjustments = (clone $adjustmentQuery)
            ->with('ingredient')
            ->latest()
            ->limit(10)
            ->get();

        return view('dashboard', compact(
            'totalProducts',
            'totalIngredients',
            'outOfStockIngredients',
            'lowStockIngredients',
            'safeStockIngredients',
            'totalRecipes',
            'lowStockList',
            'totalPurchases',
            'recentPurchases',
            'totalSales',
            'recentSales',
            'totalAdjustments',
            'recentAdjustments'
        ) + [
            'startDate' => $filters['start_date'] ?? '',
            'endDate' => $filters['end_date'] ?? '',
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ProcessesIdempotentTransactions;
use App\Models\Ingredient;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Support\InventoryNumberFormatter;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SaleController extends Controller
{
    use ProcessesIdempotentTransactions;

    /**
     * Menampilkan daftar transaksi penjualan.
     */
    public function index(Request $request)
    {
        $filters = $request->validate([
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
        ]);

        $sales = Sale::with('details.product')
            ->when(
                $filters['start_date'] ?? null,
                fn ($query, $date) => $query->whereDate('sale_date', '>=', $date)
            )
            ->when(
                $filters['end_date'] ?? null,
                fn ($query, $date) => $query->whereDate('sale_date', '<=', $date)
            )
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('sales.index', [
            'sales' => $sales,
            'startDate' => $filters['start_date'] ?? '',
            'endDate' => $filters['end_date'] ?? '',
        ]);
    }

    /**
     * Menampilkan formulir transaksi penjualan.
     */
    public function create()
    {
        $products = Product::where('is_active', true)
            ->whereHas('recipe', function ($query) {
                $query->where('is_active', true);
            })
            ->with('recipe.details.ingredient')
            ->orderBy('name')
            ->get();

        // Menyiapkan data produk dan resep untuk JavaScript.
        $productData = $products->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'recipe' => $product->recipe->details->map(function ($detail) {
                    return [
                        'name' => $detail->ingredient->name ?? 'Bahan tidak tersedia',
                        'quantity' => (float) $detail->quantity,
                        'unit' => $detail->ingredient->unit ?? '',
                        'stock' => (float) ($detail->ingredient->current_stock ?? 0),
                    ];
                })->values(),
            ];
        })->values();

        return view('sales.create', [
            'products' => $products,
            'productData' => $productData,
            'idempotencyKey' => (string) Str::uuid(),
        ]);
    }

    /**
     * Menyimpan transaksi penjualan dan mengurangi stok bahan baku.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'idempotency_key' => ['required', 'uuid'],
            'sale_date' => 'required|date',
            'notes' => 'nullable|string',
            'details' => 'required|array|min:1',
            'details.*.product_id' => 'required|distinct|exists:products,id',
            'details.*.quantity' => 'required|numeric|gt:0',
        ]);

        $this->processIdempotentTransaction(
            'sales',
            $validated['idempotency_key'],
            function () use ($validated) {

                // Claim the token before waiting on stock locks so retries fail consistently.
                $saleNumber = 'PJ-'.now()->format('YmdHis')
                    .'-'.strtoupper(Str::random(4));

                $sale = Sale::create([
                    'sale_number' => $saleNumber,
                    'idempotency_key' => $validated['idempotency_key'],
                    'sale_date' => $validated['sale_date'],
                    'total_amount' => 0,
                    'notes' => $validated['notes'] ?? null,
                ]);

                $productIds = collect($validated['details'])
                    ->pluck('product_id');

                $products = Product::whereIn('id', $productIds)
                    ->where('is_active', true)
                    ->with('recipe.details.ingredient')
                    ->get()
                    ->keyBy('id');

                if ($products->count() !== $productIds->count()) {
                    throw ValidationException::withMessages([
                        'details' => 'Terdapat produk yang tidak aktif atau tidak ditemukan.',
                    ]);
                }

                // Menghitung kebutuhan bahan baku seluruh produk.
                $ingredientRequirements = [];

                foreach ($validated['details'] as $detail) {
                    $product = $products->get($detail['product_id']);
                    $recipe = $product->recipe;

                    if (! $recipe || ! $recipe->is_active || $recipe->details->isEmpty()) {
                        throw ValidationException::withMessages([
                            'details' => 'Produk '.$product->name.' belum memiliki resep aktif.',
                        ]);
                    }

                    foreach ($recipe->details as $recipeDetail) {
                        $ingredient = $recipeDetail->ingredient;

                        if (! $ingredient || ! $ingredient->is_active) {
                            throw ValidationException::withMessages([
                                'details' => 'Terdapat bahan baku tidak aktif pada resep '.$product->name.'.',
                            ]);
                        }

                        $requiredQuantity = round(
                            (float) $recipeDetail->quantity * (float) $detail['quantity'],
                            3
                        );

                        $ingredientId = $ingredient->id;

                        $ingredientRequirements[$ingredientId] = round(
                            ($ingredientRequirements[$ingredientId] ?? 0) + $requiredQuantity,
                            3
                        );
                    }
                }

                // Mengunci stok agar tidak terjadi pengurangan bersamaan.
                $ingredients = Ingredient::whereIn(
                    'id',
                    array_keys($ingredientRequirements)
                )
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                // Memastikan stok seluruh bahan baku mencukupi.
                foreach ($ingredientRequirements as $ingredientId => $required) {
                    $ingredient = $ingredients->get($ingredientId);

                    if (! $ingredient || ! $ingredient->is_active) {
                        throw ValidationException::withMessages([
                            'details' => 'Bahan baku tidak aktif atau tidak ditemukan.',
                        ]);
                    }

                    if ($required > (float) $ingredient->current_stock) {
                        throw ValidationException::withMessages([
                            'details' => 'Stok '.$ingredient->name
                                .' tidak mencukupi. Dibutuhkan: '
                                .InventoryNumberFormatter::format($required)
                                .', tersedia: '
                                .InventoryNumberFormatter::format($ingredient->current_stock),
                        ]);
                    }
                }

                // Menyimpan detail penjualan.
                $totalAmount = 0;

                foreach ($validated['details'] as $detail) {
                    $product = $products->get($detail['product_id']);

                    $quantity = (float) $detail['quantity'];
                    $unitPrice = (float) $product->selling_price;
                    $subtotal = $quantity * $unitPrice;

                    $sale->details()->create([
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'subtotal' => $subtotal,
                    ]);

                    $totalAmount += $subtotal;
                }

                // Mengurangi stok bahan baku dan mencatat riwayatnya.
                foreach ($ingredientRequirements as $ingredientId => $required) {
                    $ingredient = $ingredients->get($ingredientId);

                    $stockBefore = (float) $ingredient->current_stock;
                    $stockAfter = round($stockBefore - $required, 3);

                    $ingredient->update([
                        'current_stock' => $stockAfter,
                    ]);

                    StockMovement::create([
                        'ingredient_id' => $ingredient->id,
                        'type' => 'sale',
                        'quantity' => $required,
                        'stock_before' => $stockBefore,
                        'stock_after' => $stockAfter,
                        'reference_number' => $saleNumber,
                        'notes' => 'Pengurangan stok otomatis dari transaksi penjualan.',
                    ]);
                }

                // Memperbarui total transaksi.
                $sale->update([
                    'total_amount' => $totalAmount,
                ]);
            }
        );

        return redirect()
            ->route('sales.index')
            ->with('success', 'Penjualan berhasil disimpan dan stok bahan baku telah diperbarui.');
    }
}

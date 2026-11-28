<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ProcessesIdempotentTransactions;
use App\Models\Ingredient;
use App\Models\Purchase;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PurchaseController extends Controller
{
    use ProcessesIdempotentTransactions;

    private const MAX_PURCHASE_QUANTITY = '999999999.999';

    private const MAX_STOCK_MILLIUNITS = 999999999999;

    public function index(Request $request)
    {
        $filters = $request->validate([
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
        ]);

        $purchases = Purchase::with('details.ingredient')
            ->when(
                $filters['start_date'] ?? null,
                fn ($query, $date) => $query->whereDate('purchase_date', '>=', $date)
            )
            ->when(
                $filters['end_date'] ?? null,
                fn ($query, $date) => $query->whereDate('purchase_date', '<=', $date)
            )
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('purchases.index', [
            'purchases' => $purchases,
            'startDate' => $filters['start_date'] ?? '',
            'endDate' => $filters['end_date'] ?? '',
        ]);
    }

    public function create(Request $request)
    {
        $ingredientIds = collect($request->session()->getOldInput('details', []))
            ->pluck('ingredient_id')
            ->filter()
            ->unique()
            ->values();

        $ingredients = Ingredient::where('is_active', true)
            ->whereIn('id', $ingredientIds)
            ->orderBy('name')
            ->get();

        return view('purchases.create', [
            'ingredients' => $ingredients,
            'hasActiveIngredients' => Ingredient::where('is_active', true)->exists(),
            'idempotencyKey' => (string) Str::uuid(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'idempotency_key' => ['required', 'uuid'],
            'purchase_date' => 'required|date',
            'notes' => 'nullable|string',
            'details' => 'required|array|min:1',
            'details.*.ingredient_id' => 'required|distinct|exists:ingredients,id',
            'details.*.quantity' => [
                'required',
                'numeric',
                'gt:0',
                'decimal:0,3',
                'max:'.self::MAX_PURCHASE_QUANTITY,
            ],
        ]);

        $this->processIdempotentTransaction(
            'purchases',
            $validated['idempotency_key'],
            function () use ($validated) {

                $totalAmount = 0;

                $purchase = Purchase::create([
                    'purchase_number' => 'PB-'.now()->format('YmdHis').'-'.strtoupper(Str::random(4)),
                    'idempotency_key' => $validated['idempotency_key'],
                    'purchase_date' => $validated['purchase_date'],
                    'total_amount' => 0,
                    'notes' => $validated['notes'] ?? null,
                ]);

                foreach ($validated['details'] as $detail) {

                    $ingredient = Ingredient::where('id', $detail['ingredient_id'])
                        ->where('is_active', true)
                        ->lockForUpdate()
                        ->first();

                    if (! $ingredient) {
                        throw ValidationException::withMessages([
                            'details' => 'Terdapat bahan baku yang tidak aktif atau tidak ditemukan.',
                        ]);
                    }

                    $quantity = $detail['quantity'];
                    $stockBefore = $ingredient->current_stock;
                    $quantityMilliunits = (int) round((float) $quantity * 1000);
                    $stockBeforeMilliunits = (int) round((float) $stockBefore * 1000);

                    if ($stockBeforeMilliunits + $quantityMilliunits > self::MAX_STOCK_MILLIUNITS) {
                        throw ValidationException::withMessages([
                            'details' => 'Jumlah pembelian melebihi kapasitas maksimum stok bahan baku.',
                        ]);
                    }

                    $unitPrice = (float) ($ingredient->purchase_price ?? 0);
                    $subtotal = $quantity * $unitPrice;

                    // Simpan detail pembelian
                    $purchase->details()->create([
                        'ingredient_id' => $ingredient->id,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'subtotal' => $subtotal,
                    ]);

                    // Tambahkan stok bahan baku
                    $ingredient->increment('current_stock', $quantity);

                    // Ambil stok setelah pembelian
                    $stockAfter = $ingredient->fresh()->current_stock;

                    // Perbarui harga pembelian terakhir
                    $ingredient->update([
                        'purchase_price' => $unitPrice,
                    ]);

                    // Catat riwayat pergerakan stok
                    StockMovement::create([
                        'ingredient_id' => $ingredient->id,
                        'type' => 'purchase',
                        'quantity' => $quantity,
                        'stock_before' => $stockBefore,
                        'stock_after' => $stockAfter,
                        'reference_number' => $purchase->purchase_number,
                        'notes' => 'Penambahan stok dari transaksi pembelian.',
                    ]);

                    $totalAmount += $subtotal;
                }

                // Perbarui total pembelian
                $purchase->update([
                    'total_amount' => $totalAmount,
                ]);
            }
        );

        return redirect()
            ->route('purchases.index')
            ->with('success', 'Pembelian berhasil disimpan dan stok bahan baku telah diperbarui.');
    }
}

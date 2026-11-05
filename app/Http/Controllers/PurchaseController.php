<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\Ingredient;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PurchaseController extends Controller
{
    public function index()
    {
        $purchases = Purchase::with('details.ingredient')
            ->latest()
            ->paginate(10);

        return view('purchases.index', compact('purchases'));
    }

    public function create()
    {
        $ingredients = Ingredient::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('purchases.create', compact('ingredients'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'purchase_date' => 'required|date',
            'notes' => 'nullable|string',
            'details' => 'required|array|min:1',
            'details.*.ingredient_id' => 'required|distinct|exists:ingredients,id',
            'details.*.quantity' => 'required|numeric|gt:0',
            'details.*.unit_price' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($validated) {

            $totalAmount = 0;

            $purchase = Purchase::create([
                'purchase_number' => 'PB-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(4)),
                'purchase_date' => $validated['purchase_date'],
                'total_amount' => 0,
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($validated['details'] as $detail) {

                $ingredient = Ingredient::where('id', $detail['ingredient_id'])
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->first();

                if (!$ingredient) {
                    throw ValidationException::withMessages([
                        'details' => 'Terdapat bahan baku yang tidak aktif atau tidak ditemukan.',
                    ]);
                }

                $quantity = $detail['quantity'];
                $unitPrice = $detail['unit_price'];
                $subtotal = $quantity * $unitPrice;

                // Simpan detail pembelian
                $purchase->details()->create([
                    'ingredient_id' => $ingredient->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                ]);

                // Ambil stok sebelum pembelian
                $stockBefore = $ingredient->current_stock;

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
        });

        return redirect()
            ->route('purchases.index')
            ->with('success', 'Pembelian berhasil disimpan dan stok bahan baku telah diperbarui.');
    }
}

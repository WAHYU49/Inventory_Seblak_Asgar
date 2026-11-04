<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\Ingredient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        $totalAmount = 0;

        DB::transaction(function () use ($validated, &$totalAmount) {

            $purchase = Purchase::create([
                'purchase_number' => 'PB-' . now()->format('YmdHis') . '-' . strtoupper(\Illuminate\Support\Str::random(4)),
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
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'details' => 'Terdapat bahan baku yang tidak aktif atau tidak ditemukan.',
                    ]);
                }

                $quantity = $detail['quantity'];
                $unitPrice = $detail['unit_price'];
                $subtotal = $quantity * $unitPrice;

                $purchase->details()->create([
                    'ingredient_id' => $ingredient->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                ]);

                $ingredient->increment('current_stock', $quantity);

                $ingredient->update([
                    'purchase_price' => $unitPrice,
                ]);

                $totalAmount += $subtotal;
            }

            $purchase->update([
                'total_amount' => $totalAmount,
            ]);
        });

        return redirect()
            ->route('purchases.index')
            ->with('success', 'Pembelian berhasil disimpan dan stok bahan baku telah diperbarui.');
    }
}

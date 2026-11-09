<?php

namespace App\Http\Controllers;

use App\Models\StockAdjustment;
use App\Models\StockMovement;
use App\Models\Ingredient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StockAdjustmentController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
        ]);

        $adjustments = StockAdjustment::with('ingredient')
            ->when(
                $filters['start_date'] ?? null,
                fn($query, $date) => $query->whereDate('adjustment_date', '>=', $date)
            )
            ->when(
                $filters['end_date'] ?? null,
                fn($query, $date) => $query->whereDate('adjustment_date', '<=', $date)
            )
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('stock_adjustments.index', [
            'adjustments' => $adjustments,
            'startDate' => $filters['start_date'] ?? '',
            'endDate' => $filters['end_date'] ?? '',
        ]);
    }

    public function create()
    {
        $ingredients = Ingredient::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('stock_adjustments.create', compact('ingredients'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'adjustment_date' => 'required|date',
            'ingredient_id' => 'required|exists:ingredients,id',
            'type' => 'required|in:increase,decrease',
            'quantity' => 'required|numeric|gt:0',
            'reason' => 'required|string|max:255',
            'notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated) {

            $ingredient = Ingredient::where('id', $validated['ingredient_id'])
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();

            if (!$ingredient) {
                throw ValidationException::withMessages([
                    'ingredient_id' => 'Bahan baku tidak aktif atau tidak ditemukan.',
                ]);
            }

            $stockBefore = (float) $ingredient->current_stock;
            $quantity = (float) $validated['quantity'];

            if ($validated['type'] === 'decrease' && $quantity > $stockBefore) {
                throw ValidationException::withMessages([
                    'quantity' => 'Jumlah pengurangan melebihi stok yang tersedia.',
                ]);
            }

            if ($validated['type'] === 'increase') {
                $stockAfter = $stockBefore + $quantity;
            } else {
                $stockAfter = $stockBefore - $quantity;
            }

            // Perbarui stok bahan baku
            $ingredient->update([
                'current_stock' => $stockAfter,
            ]);

            // Buat nomor penyesuaian
            $adjustmentNumber = 'SA-' . now()->format('YmdHis')
                . '-' . strtoupper(Str::random(4));

            // Simpan data penyesuaian
            StockAdjustment::create([
                'adjustment_number' => $adjustmentNumber,
                'ingredient_id' => $ingredient->id,
                'type' => $validated['type'],
                'quantity' => $quantity,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'reason' => $validated['reason'],
                'notes' => $validated['notes'] ?? null,
                'adjustment_date' => $validated['adjustment_date'],
            ]);

            // Catat riwayat pergerakan stok
            StockMovement::create([
                'ingredient_id' => $ingredient->id,
                'type' => 'adjustment',
                'quantity' => $quantity,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'reference_number' => $adjustmentNumber,
                'notes' => $validated['reason']
                    . (!empty($validated['notes'])
                        ? ' - ' . $validated['notes']
                        : ''),
            ]);
        });

        return redirect()
            ->route('stock-adjustments.index')
            ->with('success', 'Penyesuaian stok berhasil disimpan.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IngredientController extends Controller
{
    // Menampilkan semua bahan baku
    public function index(Request $request)
    {
        $validated = $request->validate([
            'stock_status' => ['nullable', 'in:empty,low,safe'],
        ]);

        $query = Ingredient::query();

        match ($validated['stock_status'] ?? null) {
            'empty' => $query->where('is_active', true)
                ->where('current_stock', '<=', 0),
            'low' => $query->where('is_active', true)
                ->where('current_stock', '>', 0)
                ->whereColumn('current_stock', '<=', 'minimum_stock'),
            'safe' => $query->where('is_active', true)
                ->whereColumn('current_stock', '>', 'minimum_stock'),
            default => null,
        };

        $ingredients = $query->latest()->paginate(10)->withQueryString();

        return view('ingredients.index', [
            'ingredients' => $ingredients,
            'stockStatus' => $validated['stock_status'] ?? '',
        ]);
    }

    // Menampilkan form tambah bahan baku
    public function create()
    {
        return view('ingredients.create');
    }

    // Menyimpan bahan baku baru
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:ingredients,code',
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'unit' => 'required|string|max:30',
            'current_stock' => 'required|numeric|min:0',
            'minimum_stock' => 'required|numeric|min:0',
        ]);

        $validated['is_active'] = true;

        DB::transaction(function () use ($validated) {
            $ingredient = Ingredient::create($validated);
            $initialStock = (float) $ingredient->current_stock;

            if ($initialStock > 0) {
                StockMovement::create([
                    'ingredient_id' => $ingredient->id,
                    'type' => 'initial',
                    'quantity' => $initialStock,
                    'stock_before' => 0,
                    'stock_after' => $initialStock,
                    'notes' => 'Stok awal saat bahan baku dibuat.',
                ]);
            }
        });

        return redirect()
            ->route('ingredients.index')
            ->with('success', 'Bahan baku berhasil ditambahkan.');
    }

    // Menampilkan form edit bahan baku
    public function edit(Ingredient $ingredient)
    {
        return view('ingredients.edit', compact('ingredient'));
    }

    // Memperbarui data bahan baku
    public function update(Request $request, Ingredient $ingredient)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:ingredients,code,' . $ingredient->id,
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'unit' => 'required|string|max:30',
            'minimum_stock' => 'required|numeric|min:0',
        ]);

        if (
            $validated['unit'] !== $ingredient->unit
            && $ingredient->recipeDetails()->exists()
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'unit' => 'Satuan tidak dapat diubah karena bahan baku sudah digunakan dalam resep.',
                ]);
        }

        $ingredient->update($validated);

        return redirect()
            ->route('ingredients.index')
            ->with('success', 'Data bahan baku berhasil diperbarui.');
    }

    // Menonaktifkan bahan baku, bukan menghapus permanen
    public function destroy(Ingredient $ingredient)
    {
        $ingredient->update([
            'is_active' => false,
        ]);

        return redirect()
            ->route('ingredients.index')
            ->with('success', 'Bahan baku berhasil dinonaktifkan.');
    }
}

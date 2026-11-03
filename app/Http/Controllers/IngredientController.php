<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use Illuminate\Http\Request;

class IngredientController extends Controller
{
    // Menampilkan semua bahan baku
    public function index()
    {
        $ingredients = Ingredient::latest()->paginate(10);

        return view('ingredients.index', compact('ingredients'));
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
            'unit' => 'required|string|max:30',
            'current_stock' => 'required|numeric|min:0',
            'minimum_stock' => 'required|numeric|min:0',
            'purchase_price' => 'nullable|numeric|min:0',
        ]);

        $validated['is_active'] = true;

        Ingredient::create($validated);

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
            'unit' => 'required|string|max:30',
            'minimum_stock' => 'required|numeric|min:0',
            'purchase_price' => 'nullable|numeric|min:0',
        ]);

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

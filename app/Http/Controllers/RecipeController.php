<?php

namespace App\Http\Controllers;

use App\Models\Recipe;
use App\Models\Product;
use App\Models\Ingredient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RecipeController extends Controller
{
    // Menampilkan daftar resep
    public function index()
    {
        $recipes = Recipe::with([
            'product',
            'details.ingredient'
        ])->latest()->paginate(10);

        return view('recipes.index', compact('recipes'));
    }

    // Menampilkan form tambah resep
    public function create()
    {
        $products = Product::where('is_active', true)
            ->whereDoesntHave('recipe')
            ->orderBy('name')
            ->get();

        $ingredients = Ingredient::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('recipes.create', compact(
            'products',
            'ingredients'
        ));
    }

    // Menyimpan resep dan komposisi bahan baku
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => [
                'required',
                'exists:products,id',
                Rule::unique('recipes', 'product_id'),
            ],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',

            'details' => 'required|array|min:1',
            'details.*.ingredient_id' => [
                'required',
                'distinct',
                'exists:ingredients,id',
            ],
            'details.*.quantity' => 'required|numeric|gt:0',
        ]);

        $product = Product::findOrFail($validated['product_id']);

        if (!$product->is_active) {
            return back()
                ->withInput()
                ->withErrors(['product_id' => 'Produk tidak aktif.']);
        }

        $ingredientIds = collect($validated['details'])
            ->pluck('ingredient_id');

        $activeIngredients = Ingredient::whereIn('id', $ingredientIds)
            ->where('is_active', true)
            ->count();

        if ($activeIngredients !== $ingredientIds->count()) {
            return back()
                ->withInput()
                ->withErrors(['details' => 'Terdapat bahan baku yang tidak aktif.']);
        }

        DB::transaction(function () use ($validated) {
            $recipe = Recipe::create([
                'product_id' => $validated['product_id'],
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'is_active' => true,
            ]);

            foreach ($validated['details'] as $detail) {
                $recipe->details()->create([
                    'ingredient_id' => $detail['ingredient_id'],
                    'quantity' => $detail['quantity'],
                ]);
            }
        });

        return redirect()
            ->route('recipes.index')
            ->with('success', 'Resep berhasil ditambahkan.');
    }

    // Menampilkan detail resep
    public function show(Recipe $recipe)
    {
        return redirect()->route('recipes.edit', $recipe);
    }

    // Menampilkan form edit resep
    public function edit(Recipe $recipe)
    {
        $recipe->load('details.ingredient');

        $ingredients = Ingredient::where('is_active', true)
            ->orWhereIn('id', $recipe->details->pluck('ingredient_id'))
            ->orderBy('name')
            ->get();

        return view('recipes.edit', compact(
            'recipe',
            'ingredients'
        ));
    }

    // Memperbarui resep dan komposisi
    public function update(Request $request, Recipe $recipe)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',

            'details' => 'required|array|min:1',
            'details.*.ingredient_id' => [
                'required',
                'distinct',
                'exists:ingredients,id',
            ],
            'details.*.quantity' => 'required|numeric|gt:0',
        ]);

        $ingredientIds = collect($validated['details'])
            ->pluck('ingredient_id');

        $existingIngredientIds = $recipe->details()
            ->pluck('ingredient_id');

        $activeIngredients = Ingredient::whereIn('id', $ingredientIds)
            ->where(function ($query) use ($existingIngredientIds) {
                $query->where('is_active', true)
                    ->orWhereIn('id', $existingIngredientIds);
            })
            ->count();

        if ($activeIngredients !== $ingredientIds->count()) {
            return back()
                ->withInput()
                ->withErrors(['details' => 'Terdapat bahan baku yang tidak aktif.']);
        }

        DB::transaction(function () use ($recipe, $validated) {
            $recipe->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
            ]);

            $recipe->details()->delete();

            foreach ($validated['details'] as $detail) {
                $recipe->details()->create([
                    'ingredient_id' => $detail['ingredient_id'],
                    'quantity' => $detail['quantity'],
                ]);
            }
        });

        return redirect()
            ->route('recipes.index')
            ->with('success', 'Resep berhasil diperbarui.');
    }

    // Menghapus resep
    public function destroy(Recipe $recipe)
    {
        $recipe->delete();

        return redirect()
            ->route('recipes.index')
            ->with('success', 'Resep berhasil dihapus.');
    }
}

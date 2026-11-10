<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\RecipeDetail;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IngredientControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_unit_cannot_be_changed_when_ingredient_is_used_in_a_recipe(): void
    {
        $ingredient = $this->createIngredient('BB-RECIPE', 'gram');
        $recipe = $this->createRecipe();

        RecipeDetail::create([
            'recipe_id' => $recipe->id,
            'ingredient_id' => $ingredient->id,
            'quantity' => 5,
        ]);

        $this->actingAs(User::factory()->create())
            ->from(route('ingredients.edit', $ingredient))
            ->followingRedirects()
            ->put(route('ingredients.update', $ingredient), [
                'code' => $ingredient->code,
                'name' => $ingredient->name,
                'unit' => 'kg',
                'minimum_stock' => $ingredient->minimum_stock,
            ])
            ->assertOk()
            ->assertSee('Satuan tidak dapat diubah karena bahan baku sudah digunakan dalam resep.');

        $this->assertSame('gram', $ingredient->fresh()->unit);
        $this->assertSame(5.0, (float) $ingredient->recipeDetails()->first()->quantity);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_unit_can_be_changed_when_ingredient_is_not_used_in_a_recipe(): void
    {
        $ingredient = $this->createIngredient('BB-NO-RECIPE', 'gram');

        $this->actingAs(User::factory()->create())
            ->put(route('ingredients.update', $ingredient), [
                'code' => $ingredient->code,
                'name' => $ingredient->name,
                'unit' => 'kg',
                'minimum_stock' => $ingredient->minimum_stock,
            ])
            ->assertRedirect(route('ingredients.index'));

        $this->assertSame('kg', $ingredient->fresh()->unit);
        $this->assertSame(10.0, (float) $ingredient->fresh()->current_stock);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_creating_ingredient_with_initial_stock_records_one_movement_without_incrementing_stock(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('ingredients.store'), $this->ingredientInput(7.5))
            ->assertRedirect(route('ingredients.index'));

        $ingredient = Ingredient::where('code', 'BB-INITIAL')->firstOrFail();
        $movement = StockMovement::where('ingredient_id', $ingredient->id)->firstOrFail();

        $this->assertSame(7.5, (float) $ingredient->current_stock);
        $this->assertSame(1, StockMovement::where('ingredient_id', $ingredient->id)->count());
        $this->assertSame('initial', $movement->type);
        $this->assertSame(7.5, (float) $movement->quantity);
        $this->assertSame(0.0, (float) $movement->stock_before);
        $this->assertSame(7.5, (float) $movement->stock_after);

        $this->get(route('stock-movements.index'))
            ->assertOk()
            ->assertSee('Stok Awal');
    }

    public function test_creating_ingredient_with_zero_initial_stock_does_not_record_a_movement(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('ingredients.store'), $this->ingredientInput(0))
            ->assertRedirect(route('ingredients.index'));

        $ingredient = Ingredient::where('code', 'BB-INITIAL')->firstOrFail();

        $this->assertSame(0.0, (float) $ingredient->current_stock);
        $this->assertSame(0, StockMovement::where('ingredient_id', $ingredient->id)->count());
    }

    private function createIngredient(string $code, string $unit): Ingredient
    {
        return Ingredient::create([
            'code' => $code,
            'name' => 'Bahan Uji',
            'unit' => $unit,
            'current_stock' => 10,
            'minimum_stock' => 2,
        ]);
    }

    private function createRecipe(): Recipe
    {
        $category = Category::create(['name' => 'Menu Uji']);
        $product = Product::create([
            'category_id' => $category->id,
            'code' => 'PR-RECIPE-TEST',
            'name' => 'Produk Uji',
            'selling_price' => 0,
        ]);

        return Recipe::create([
            'product_id' => $product->id,
            'name' => 'Resep Uji',
        ]);
    }

    private function ingredientInput(float $initialStock): array
    {
        return [
            'code' => 'BB-INITIAL',
            'name' => 'Bahan Stok Awal',
            'unit' => 'gram',
            'current_stock' => $initialStock,
            'minimum_stock' => 1,
        ];
    }
}

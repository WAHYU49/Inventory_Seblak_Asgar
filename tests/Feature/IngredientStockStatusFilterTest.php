<?php

namespace Tests\Feature;

use App\Models\Ingredient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IngredientStockStatusFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_status_filter_shows_only_matching_active_ingredients(): void
    {
        $this->actingAs(User::factory()->create());

        $this->createIngredient('BB-EMPTY', 'Bahan Habis', 0, 5, true);
        $this->createIngredient('BB-LOW', 'Bahan Menipis', 2, 5, true);
        $this->createIngredient('BB-SAFE', 'Bahan Aman', 8, 5, true);
        $this->createIngredient('BB-INACTIVE', 'Bahan Nonaktif', 0, 5, false);

        $this->get(route('ingredients.index', ['stock_status' => 'empty']))
            ->assertOk()
            ->assertSee('BB-EMPTY')
            ->assertDontSee('BB-LOW')
            ->assertDontSee('BB-SAFE')
            ->assertDontSee('BB-INACTIVE')
            ->assertSee('Stok Habis');

        $this->get(route('ingredients.index', ['stock_status' => 'low']))
            ->assertOk()
            ->assertSee('BB-LOW')
            ->assertDontSee('BB-EMPTY')
            ->assertDontSee('BB-SAFE')
            ->assertDontSee('BB-INACTIVE')
            ->assertSee('Stok Menipis');

        $this->get(route('ingredients.index', ['stock_status' => 'safe']))
            ->assertOk()
            ->assertSee('BB-SAFE')
            ->assertDontSee('BB-EMPTY')
            ->assertDontSee('BB-LOW')
            ->assertDontSee('BB-INACTIVE')
            ->assertSee('Stok Aman');

        $this->get(route('ingredients.index', ['stock_status' => 'unknown']))
            ->assertRedirect()
            ->assertSessionHasErrors('stock_status');

        $this->assertSame(0.0, (float) Ingredient::where('code', 'BB-EMPTY')->value('current_stock'));
        $this->assertDatabaseCount('ingredients', 4);
    }

    private function createIngredient(
        string $code,
        string $name,
        float $stock,
        float $minimum,
        bool $active
    ): void {
        Ingredient::create([
            'code' => $code,
            'name' => $name,
            'unit' => 'pcs',
            'current_stock' => $stock,
            'minimum_stock' => $minimum,
            'is_active' => $active,
        ]);
    }
}

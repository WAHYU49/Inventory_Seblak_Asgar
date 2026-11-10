<?php

namespace Tests\Feature;

use App\Models\Ingredient;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\Recipe;
use App\Models\RecipeDetail;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\StockAdjustment;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryNumberDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_pages_display_stock_and_quantities_in_indonesian_format(): void
    {
        $this->actingAs(User::factory()->create());

        $ingredient = Ingredient::create([
            'code' => 'BB-FORMAT',
            'name' => 'Bahan Format',
            'unit' => 'gram',
            'current_stock' => '10.500',
            'minimum_stock' => '11.000',
            'is_active' => true,
        ]);
        $product = Product::create([
            'code' => 'PR-FORMAT',
            'name' => 'Produk Format',
            'selling_price' => 0,
            'is_active' => true,
        ]);
        $recipe = Recipe::create([
            'product_id' => $product->id,
            'name' => 'Resep Format',
            'is_active' => true,
        ]);
        RecipeDetail::create([
            'recipe_id' => $recipe->id,
            'ingredient_id' => $ingredient->id,
            'quantity' => '10.250',
        ]);

        $purchase = Purchase::create([
            'purchase_number' => 'PB-FORMAT',
            'purchase_date' => '2026-11-10',
            'total_amount' => 0,
        ]);
        PurchaseDetail::create([
            'purchase_id' => $purchase->id,
            'ingredient_id' => $ingredient->id,
            'quantity' => '10.500',
            'unit_price' => 0,
            'subtotal' => 0,
        ]);

        $sale = Sale::create([
            'sale_number' => 'PJ-FORMAT',
            'sale_date' => '2026-11-10',
            'total_amount' => 0,
        ]);
        SaleDetail::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'quantity' => '1.500',
            'unit_price' => 0,
            'subtotal' => 0,
        ]);

        StockAdjustment::create([
            'adjustment_number' => 'SA-FORMAT',
            'ingredient_id' => $ingredient->id,
            'type' => 'increase',
            'quantity' => '10.250',
            'stock_before' => '1500.000',
            'stock_after' => '1510.250',
            'reason' => 'Stok opname',
            'adjustment_date' => '2026-11-10',
        ]);
        StockMovement::create([
            'ingredient_id' => $ingredient->id,
            'type' => 'purchase',
            'quantity' => '10.500',
            'stock_before' => '1234.567',
            'stock_after' => '1245.067',
        ]);

        $this->get(route('ingredients.index'))
            ->assertOk()
            ->assertSee('10,5')
            ->assertSee('11');

        $this->get(route('ingredients.edit', $ingredient))
            ->assertOk()
            ->assertSee('value="10,5 gram"', false);

        $this->get(route('recipes.index'))
            ->assertOk()
            ->assertSee('(10,25 gram)');

        $this->get(route('purchases.index'))
            ->assertOk()
            ->assertSee('10,5')
            ->assertSee('gram');

        $this->get(route('sales.index'))
            ->assertOk()
            ->assertSee('(1,5 x)');

        $this->get(route('stock-adjustments.index'))
            ->assertOk()
            ->assertSee('10,25')
            ->assertSee('1.500')
            ->assertSee('1.510,25');

        $this->get(route('stock-movements.index'))
            ->assertOk()
            ->assertSee('10,5')
            ->assertSee('1.234,567')
            ->assertSee('1.245,067');

        $this->get(route('stock-adjustments.create'))
            ->assertOk()
            ->assertSee('data-stock="10,5"', false);

        $this->get(route('sales.create'))
            ->assertOk()
            ->assertSee('formatQuantity(item.quantity)')
            ->assertSee('formatQuantity(item.stock)');

        $this->from(route('sales.create'))
            ->post(route('sales.store'), [
                'sale_date' => '2026-11-10',
                'details' => [
                    ['product_id' => $product->id, 'quantity' => 2],
                ],
            ])
            ->assertRedirect(route('sales.create'))
            ->assertSessionHasErrors([
                'details' => 'Stok Bahan Format tidak mencukupi. Dibutuhkan: 20,5, tersedia: 10,5',
            ]);

        $this->assertSame(10.5, (float) $ingredient->fresh()->current_stock);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('10,5')
            ->assertSee('gram')
            ->assertSee('10,25');
    }

    public function test_display_formatting_does_not_change_stock_values_or_transactions(): void
    {
        $ingredient = Ingredient::create([
            'code' => 'BB-PRESERVE',
            'name' => 'Bahan Presisi',
            'unit' => 'gram',
            'current_stock' => '10.500',
            'minimum_stock' => '2.250',
        ]);

        $stockBeforeRequest = $ingredient->current_stock;

        $this->actingAs(User::factory()->create())
            ->get(route('ingredients.index'))
            ->assertOk();

        $this->assertSame($stockBeforeRequest, $ingredient->fresh()->current_stock);
        $this->assertDatabaseCount('stock_movements', 0);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Recipe;
use App\Models\Sale;
use App\Models\StockAdjustment;
use App\Models\StockMovement;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WibTimestampDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_pages_display_timestamps_in_wib_and_business_dates_without_time_zone_conversion(): void
    {
        $this->actingAs(User::factory()->create());

        $timestamp = CarbonImmutable::parse('2026-11-10 04:06:00', 'UTC');
        $category = Category::create(['name' => 'Kategori WIB']);
        $ingredient = Ingredient::create([
            'code' => 'BB-WIB',
            'name' => 'Bahan WIB',
            'unit' => 'gram',
            'current_stock' => 2,
            'minimum_stock' => 3,
            'is_active' => true,
        ]);
        $product = Product::create([
            'category_id' => $category->id,
            'code' => 'PR-WIB',
            'name' => 'Produk WIB',
            'selling_price' => 0,
            'is_active' => true,
        ]);
        $recipe = Recipe::create([
            'product_id' => $product->id,
            'name' => 'Resep WIB',
            'is_active' => true,
        ]);
        $purchase = Purchase::create([
            'purchase_number' => 'PB-WIB',
            'purchase_date' => '2026-11-10',
            'total_amount' => 0,
        ]);
        $sale = Sale::create([
            'sale_number' => 'PJ-WIB',
            'sale_date' => '2026-11-10',
            'total_amount' => 0,
        ]);
        $adjustment = StockAdjustment::create([
            'adjustment_number' => 'SA-WIB',
            'ingredient_id' => $ingredient->id,
            'type' => 'increase',
            'quantity' => 1,
            'stock_before' => 2,
            'stock_after' => 3,
            'reason' => 'Pengujian format waktu',
            'adjustment_date' => '2026-11-10',
        ]);
        $movement = StockMovement::create([
            'ingredient_id' => $ingredient->id,
            'type' => 'adjustment',
            'quantity' => 1,
            'stock_before' => 2,
            'stock_after' => 3,
        ]);

        foreach ([$category, $ingredient, $product, $recipe, $purchase, $sale, $adjustment, $movement] as $model) {
            $this->setTimestamps($model, $timestamp);
        }

        $expectedTimestamp = '10-11-2026 11:06';
        $expectedBusinessDate = '10-11-2026';

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Waktu Input (WIB)')
            ->assertSee($expectedTimestamp)
            ->assertSee($expectedBusinessDate);

        $this->get(route('ingredients.index'))
            ->assertOk()
            ->assertSee('Waktu Input (WIB)')
            ->assertSee($expectedTimestamp);

        $this->get(route('products.index'))
            ->assertOk()
            ->assertSee('Waktu Input (WIB)')
            ->assertSee($expectedTimestamp);

        $this->get(route('categories.index'))
            ->assertOk()
            ->assertSee('Waktu Input (WIB)')
            ->assertSee($expectedTimestamp);

        $this->get(route('recipes.index'))
            ->assertOk()
            ->assertSee('Waktu Input (WIB)')
            ->assertSee($expectedTimestamp);

        $this->get(route('purchases.index'))
            ->assertOk()
            ->assertSee('Waktu Input (WIB)')
            ->assertSee($expectedTimestamp)
            ->assertSee($expectedBusinessDate);

        $this->get(route('sales.index'))
            ->assertOk()
            ->assertSee('Waktu Input (WIB)')
            ->assertSee($expectedTimestamp)
            ->assertSee($expectedBusinessDate);

        $this->get(route('stock-adjustments.index'))
            ->assertOk()
            ->assertSee('Waktu Input (WIB)')
            ->assertSee($expectedTimestamp)
            ->assertSee($expectedBusinessDate)
            ->assertDontSee('10/11/2026');

        $this->get(route('stock-movements.index'))
            ->assertOk()
            ->assertSee('Waktu (WIB)')
            ->assertSee($expectedTimestamp);
    }

    private function setTimestamps(Model $model, CarbonImmutable $timestamp): void
    {
        $model->forceFill([
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ])->save();
    }
}

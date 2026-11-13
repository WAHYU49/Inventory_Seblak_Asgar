<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Recipe;
use App\Models\RecipeDetail;
use App\Models\Sale;
use App\Models\StockAdjustment;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class InventoryTransactionIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_token_cannot_increment_stock_twice_and_new_token_is_accepted(): void
    {
        $this->actingAs(User::factory()->create());
        $ingredient = $this->createIngredient('BB-IDEMP-PURCHASE', 4);
        $payload = [
            'idempotency_key' => (string) Str::uuid(),
            'purchase_date' => '2026-11-13',
            'details' => [
                ['ingredient_id' => $ingredient->id, 'quantity' => 2],
            ],
        ];

        $this->post(route('purchases.store'), $payload)
            ->assertRedirect(route('purchases.index'));

        $this->from(route('purchases.create'))
            ->post(route('purchases.store'), $payload)
            ->assertRedirect(route('purchases.create'))
            ->assertSessionHasErrors([
                'idempotency_key' => 'Transaksi ini sudah pernah diproses. Silakan buat transaksi baru.',
            ]);

        $this->assertSame(6.0, (float) $ingredient->fresh()->current_stock);
        $this->assertDatabaseCount('purchases', 1);
        $this->assertDatabaseCount('stock_movements', 1);

        $payload['idempotency_key'] = (string) Str::uuid();
        $this->post(route('purchases.store'), $payload)
            ->assertRedirect(route('purchases.index'));

        $this->assertSame(8.0, (float) $ingredient->fresh()->current_stock);
        $this->assertDatabaseCount('purchases', 2);
        $this->assertDatabaseCount('stock_movements', 2);
    }

    public function test_sale_token_cannot_decrement_stock_twice_and_new_token_is_accepted(): void
    {
        $this->actingAs(User::factory()->create());
        $ingredient = $this->createIngredient('BB-IDEMP-SALE', 10);
        $product = $this->createProductWithIngredient($ingredient, 2);
        $payload = [
            'idempotency_key' => (string) Str::uuid(),
            'sale_date' => '2026-11-13',
            'details' => [
                ['product_id' => $product->id, 'quantity' => 2],
            ],
        ];

        $this->post(route('sales.store'), $payload)
            ->assertRedirect(route('sales.index'));

        $this->from(route('sales.create'))
            ->post(route('sales.store'), $payload)
            ->assertRedirect(route('sales.create'))
            ->assertSessionHasErrors([
                'idempotency_key' => 'Transaksi ini sudah pernah diproses. Silakan buat transaksi baru.',
            ]);

        $this->assertSame(6.0, (float) $ingredient->fresh()->current_stock);
        $this->assertDatabaseCount('sales', 1);
        $this->assertDatabaseCount('stock_movements', 1);

        $payload['idempotency_key'] = (string) Str::uuid();
        $this->post(route('sales.store'), $payload)
            ->assertRedirect(route('sales.index'));

        $this->assertSame(2.0, (float) $ingredient->fresh()->current_stock);
        $this->assertDatabaseCount('sales', 2);
        $this->assertDatabaseCount('stock_movements', 2);
    }

    public function test_adjustment_token_cannot_change_stock_twice_and_new_token_is_accepted(): void
    {
        $this->actingAs(User::factory()->create());
        $ingredient = $this->createIngredient('BB-IDEMP-ADJUSTMENT', 10);
        $payload = [
            'idempotency_key' => (string) Str::uuid(),
            'adjustment_date' => '2026-11-13',
            'ingredient_id' => $ingredient->id,
            'type' => 'increase',
            'quantity' => 3,
            'reason' => 'Stok opname',
        ];

        $this->post(route('stock-adjustments.store'), $payload)
            ->assertRedirect(route('stock-adjustments.index'));

        $this->from(route('stock-adjustments.create'))
            ->post(route('stock-adjustments.store'), $payload)
            ->assertRedirect(route('stock-adjustments.create'))
            ->assertSessionHasErrors([
                'idempotency_key' => 'Transaksi ini sudah pernah diproses. Silakan buat transaksi baru.',
            ]);

        $this->assertSame(13.0, (float) $ingredient->fresh()->current_stock);
        $this->assertDatabaseCount('stock_adjustments', 1);
        $this->assertDatabaseCount('stock_movements', 1);

        $payload['idempotency_key'] = (string) Str::uuid();
        $payload['type'] = 'decrease';
        $payload['quantity'] = 2;
        $this->post(route('stock-adjustments.store'), $payload)
            ->assertRedirect(route('stock-adjustments.index'));

        $this->assertSame(11.0, (float) $ingredient->fresh()->current_stock);
        $this->assertDatabaseCount('stock_adjustments', 2);
        $this->assertDatabaseCount('stock_movements', 2);
    }

    public function test_failed_purchase_rolls_back_stock_transaction_and_movement(): void
    {
        $this->actingAs(User::factory()->create());
        $activeIngredient = $this->createIngredient('BB-IDEMP-ROLLBACK', 4);
        $inactiveIngredient = $this->createIngredient('BB-IDEMP-INACTIVE', 5, false);

        $this->post(route('purchases.store'), [
            'idempotency_key' => (string) Str::uuid(),
            'purchase_date' => '2026-11-13',
            'details' => [
                ['ingredient_id' => $activeIngredient->id, 'quantity' => 2],
                ['ingredient_id' => $inactiveIngredient->id, 'quantity' => 1],
            ],
        ])->assertSessionHasErrors('details');

        $this->assertSame(4.0, (float) $activeIngredient->fresh()->current_stock);
        $this->assertDatabaseCount('purchases', 0);
        $this->assertDatabaseCount('purchase_details', 0);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_legacy_transactions_without_keys_remain_readable(): void
    {
        $this->actingAs(User::factory()->create());
        $this->createIngredient('BB-IDEMP-LEGACY', 5);
        $purchase = Purchase::create([
            'purchase_number' => 'PB-LEGACY-001',
            'purchase_date' => '2026-11-01',
            'total_amount' => 0,
        ]);
        $sale = Sale::create([
            'sale_number' => 'PJ-LEGACY-001',
            'sale_date' => '2026-11-01',
            'total_amount' => 0,
        ]);
        $adjustment = StockAdjustment::create([
            'adjustment_number' => 'SA-LEGACY-001',
            'ingredient_id' => Ingredient::firstOrFail()->id,
            'type' => 'increase',
            'quantity' => 1,
            'stock_before' => 4,
            'stock_after' => 5,
            'reason' => 'Stok opname',
            'adjustment_date' => '2026-11-01',
        ]);

        $this->assertNull($purchase->idempotency_key);
        $this->assertNull($sale->idempotency_key);
        $this->assertNull($adjustment->idempotency_key);

        $this->get(route('purchases.index'))->assertOk()->assertSee('PB-LEGACY-001');
        $this->get(route('sales.index'))->assertOk()->assertSee('PJ-LEGACY-001');
        $this->get(route('stock-adjustments.index'))->assertOk()->assertSee('SA-LEGACY-001');
    }

    public function test_database_unique_constraints_reject_duplicate_keys_for_each_module(): void
    {
        $key = (string) Str::uuid();
        $ingredient = $this->createIngredient('BB-IDEMP-UNIQUE', 5);

        $creates = [
            fn () => Purchase::create([
                'purchase_number' => 'PB-IDEMP-UNIQUE-'.Str::random(6),
                'idempotency_key' => $key,
                'purchase_date' => '2026-11-13',
                'total_amount' => 0,
            ]),
            fn () => Sale::create([
                'sale_number' => 'PJ-IDEMP-UNIQUE-'.Str::random(6),
                'idempotency_key' => $key,
                'sale_date' => '2026-11-13',
                'total_amount' => 0,
            ]),
            fn () => StockAdjustment::create([
                'adjustment_number' => 'SA-IDEMP-UNIQUE-'.Str::random(6),
                'idempotency_key' => $key,
                'ingredient_id' => $ingredient->id,
                'type' => 'increase',
                'quantity' => 1,
                'stock_before' => 5,
                'stock_after' => 6,
                'reason' => 'Stok opname',
                'adjustment_date' => '2026-11-13',
            ]),
        ];

        foreach ($creates as $create) {
            $create();

            try {
                $create();
                $this->fail('A duplicate idempotency key was accepted.');
            } catch (QueryException) {
                $this->assertTrue(true);
            }
        }
    }

    public function test_each_transaction_form_receives_a_server_generated_token(): void
    {
        $this->actingAs(User::factory()->create());
        $ingredient = $this->createIngredient('BB-IDEMP-FORMS', 10);
        $this->createProductWithIngredient($ingredient, 1);

        $keys = [
            $this->idempotencyKeyFrom($this->get(route('purchases.create'))->assertOk()->getContent()),
            $this->idempotencyKeyFrom($this->get(route('sales.create'))->assertOk()->getContent()),
            $this->idempotencyKeyFrom($this->get(route('stock-adjustments.create'))->assertOk()->getContent()),
        ];

        $this->assertCount(3, array_unique($keys));
    }

    private function createIngredient(string $code, float $stock, bool $isActive = true): Ingredient
    {
        return Ingredient::create([
            'code' => $code,
            'name' => 'Bahan '.$code,
            'unit' => 'pcs',
            'current_stock' => $stock,
            'minimum_stock' => 1,
            'purchase_price' => 100,
            'is_active' => $isActive,
        ]);
    }

    private function createProductWithIngredient(Ingredient $ingredient, float $recipeQuantity): Product
    {
        $category = Category::create(['name' => 'Kategori Uji']);
        $product = Product::create([
            'category_id' => $category->id,
            'code' => 'PR-'.Str::upper(Str::random(8)),
            'name' => 'Produk Uji',
            'selling_price' => 500,
            'is_active' => true,
        ]);
        $recipe = Recipe::create([
            'product_id' => $product->id,
            'name' => 'Resep Uji',
            'is_active' => true,
        ]);
        RecipeDetail::create([
            'recipe_id' => $recipe->id,
            'ingredient_id' => $ingredient->id,
            'quantity' => $recipeQuantity,
        ]);

        return $product;
    }

    private function idempotencyKeyFrom(string $html): string
    {
        $this->assertSame(
            1,
            preg_match('/name="idempotency_key" value="([0-9a-f-]{36})"/i', $html, $matches)
        );

        return $matches[1];
    }
}

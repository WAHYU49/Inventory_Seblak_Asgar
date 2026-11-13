<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Recipe;
use App\Models\RecipeDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class InventoryOnlyFormsTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_can_be_created_without_a_selling_price(): void
    {
        $category = Category::create(['name' => 'Menu']);

        $this->actingAs(User::factory()->create())
            ->post(route('products.store'), [
                'category_id' => $category->id,
                'code' => 'PR-NO-PRICE',
                'name' => 'Produk Tanpa Harga',
                'description' => 'Deskripsi',
            ])
            ->assertRedirect(route('products.index'));

        $product = Product::where('code', 'PR-NO-PRICE')->firstOrFail();
        $this->assertSame(0.0, (float) $product->selling_price);
    }

    public function test_product_update_without_price_preserves_existing_internal_value(): void
    {
        $category = Category::create(['name' => 'Menu']);
        $product = Product::create([
            'category_id' => $category->id,
            'code' => 'PR-KEEP-PRICE',
            'name' => 'Produk Lama',
            'selling_price' => 15000,
        ]);

        $this->actingAs(User::factory()->create())
            ->put(route('products.update', $product), [
                'category_id' => $category->id,
                'code' => 'PR-KEEP-PRICE',
                'name' => 'Produk Diperbarui',
                'description' => 'Tanpa input harga',
            ])
            ->assertRedirect(route('products.index'));

        $this->assertSame(15000.0, (float) $product->fresh()->selling_price);
    }

    public function test_ingredient_can_be_created_without_a_purchase_price(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('ingredients.store'), [
                'code' => 'BB-NO-PRICE',
                'name' => 'Bahan Tanpa Harga',
                'unit' => 'gram',
                'current_stock' => 5,
                'minimum_stock' => 2,
            ])
            ->assertRedirect(route('ingredients.index'));

        $ingredient = Ingredient::where('code', 'BB-NO-PRICE')->firstOrFail();
        $this->assertSame(0.0, (float) $ingredient->purchase_price);
    }

    public function test_purchase_form_hides_price_and_purchase_uses_stored_price_internally(): void
    {
        $admin = User::factory()->create();
        $ingredient = Ingredient::create([
            'code' => 'BB-PURCHASE',
            'name' => 'Bahan Pembelian',
            'unit' => 'pcs',
            'current_stock' => 4,
            'minimum_stock' => 1,
            'purchase_price' => 1200,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('purchases.create'))
            ->assertOk()
            ->assertDontSee('Harga')
            ->assertDontSee('Rp')
            ->assertDontSee('unit_price');

        $this->post(route('purchases.store'), [
            'idempotency_key' => (string) Str::uuid(),
            'purchase_date' => '2026-03-10',
            'details' => [
                ['ingredient_id' => $ingredient->id, 'quantity' => 2],
            ],
        ])->assertRedirect(route('purchases.index'));

        $purchase = Purchase::with('details')->firstOrFail();
        $this->assertSame(6.0, (float) $ingredient->fresh()->current_stock);
        $this->assertSame(1200.0, (float) $purchase->details->first()->unit_price);
        $this->assertSame(2400.0, (float) $purchase->total_amount);
    }

    public function test_sale_form_hides_product_price_and_transaction_total(): void
    {
        $admin = User::factory()->create();
        $ingredient = Ingredient::create([
            'code' => 'BB-SALE',
            'name' => 'Bahan Penjualan',
            'unit' => 'gram',
            'current_stock' => 10,
            'minimum_stock' => 1,
            'is_active' => true,
        ]);
        $category = Category::create(['name' => 'Menu']);
        $product = Product::create([
            'category_id' => $category->id,
            'code' => 'PR-SALE',
            'name' => 'Produk Penjualan',
            'selling_price' => 25000,
            'is_active' => true,
        ]);
        $recipe = Recipe::create([
            'product_id' => $product->id,
            'name' => 'Resep Produk',
            'is_active' => true,
        ]);
        RecipeDetail::create([
            'recipe_id' => $recipe->id,
            'ingredient_id' => $ingredient->id,
            'quantity' => 1,
        ]);

        $this->actingAs($admin)
            ->get(route('sales.create'))
            ->assertOk()
            ->assertSee('Produk Penjualan')
            ->assertDontSee('25000')
            ->assertDontSee('Rp')
            ->assertDontSee('Subtotal')
            ->assertDontSee('Total Transaksi');
    }
}

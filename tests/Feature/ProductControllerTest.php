<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_without_relations_can_be_deleted(): void
    {
        $product = $this->createProduct();

        $this->actingAs(User::factory()->create())
            ->delete(route('products.destroy', $product))
            ->assertRedirect(route('products.index'))
            ->assertSessionHas('success', 'Produk berhasil dihapus.');

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_product_used_in_a_recipe_cannot_be_deleted(): void
    {
        $product = $this->createProduct();
        $recipe = Recipe::create([
            'product_id' => $product->id,
            'name' => 'Resep Uji',
        ]);

        $this->actingAs(User::factory()->create())
            ->delete(route('products.destroy', $product))
            ->assertRedirect(route('products.index'))
            ->assertSessionHas(
                'error',
                'Produk tidak dapat dihapus karena sudah digunakan dalam resep atau riwayat transaksi. Silakan nonaktifkan produk.'
            );

        $this->assertDatabaseHas('products', ['id' => $product->id]);
        $this->assertDatabaseHas('recipes', ['id' => $recipe->id, 'product_id' => $product->id]);
    }

    public function test_product_with_sales_history_cannot_be_deleted(): void
    {
        $product = $this->createProduct();
        $sale = Sale::create([
            'sale_number' => 'PJ-TEST-1',
            'sale_date' => now()->toDateString(),
            'total_amount' => 1000,
        ]);
        $detail = SaleDetail::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 1000,
            'subtotal' => 1000,
        ]);

        $this->actingAs(User::factory()->create())
            ->delete(route('products.destroy', $product))
            ->assertRedirect(route('products.index'))
            ->assertSessionHas(
                'error',
                'Produk tidak dapat dihapus karena sudah digunakan dalam resep atau riwayat transaksi. Silakan nonaktifkan produk.'
            );

        $this->assertDatabaseHas('products', ['id' => $product->id]);
        $this->assertDatabaseHas('sale_details', ['id' => $detail->id, 'product_id' => $product->id]);
    }

    public function test_product_can_be_deactivated_without_removing_recipe_or_sales_history(): void
    {
        $product = $this->createProduct();
        $recipe = Recipe::create([
            'product_id' => $product->id,
            'name' => 'Resep Uji',
        ]);
        $sale = Sale::create([
            'sale_number' => 'PJ-TEST-2',
            'sale_date' => now()->toDateString(),
            'total_amount' => 1000,
        ]);
        $detail = SaleDetail::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 1000,
            'subtotal' => 1000,
        ]);

        $this->actingAs(User::factory()->create())
            ->patch(route('products.status', $product))
            ->assertRedirect(route('products.index'))
            ->assertSessionHas('success', 'Produk berhasil dinonaktifkan.');

        $this->assertFalse($product->fresh()->is_active);
        $this->assertDatabaseHas('recipes', ['id' => $recipe->id, 'product_id' => $product->id]);
        $this->assertDatabaseHas('sale_details', ['id' => $detail->id, 'product_id' => $product->id]);
        $this->assertSame($product->name, $detail->fresh()->product->name);

        $this->get(route('sales.index'))
            ->assertOk()
            ->assertSee($product->name);
    }

    public function test_inactive_product_is_not_offered_or_accepted_for_new_sales(): void
    {
        $product = $this->createProduct(['is_active' => false]);
        Recipe::create([
            'product_id' => $product->id,
            'name' => 'Resep Uji',
        ]);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('sales.create'))
            ->assertOk()
            ->assertDontSee($product->name);

        $this->post(route('sales.store'), [
            'idempotency_key' => 'a3506064-592f-4e16-ad71-43d6f715b752',
            'sale_date' => now()->toDateString(),
            'details' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ])
            ->assertSessionHasErrors('details');

        $this->assertDatabaseCount('sales', 0);
    }

    private function createProduct(array $attributes = []): Product
    {
        $category = Category::create(['name' => 'Kategori Uji']);

        return Product::create(array_merge([
            'category_id' => $category->id,
            'code' => 'PR-'.strtoupper(str()->random(8)),
            'name' => 'Produk Uji '.str()->random(6),
            'selling_price' => 1000,
        ], $attributes));
    }
}

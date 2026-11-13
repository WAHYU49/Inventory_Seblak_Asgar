<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogSearchFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_categories_can_be_searched_by_name_and_keep_filters_in_pagination(): void
    {
        foreach (range(1, 11) as $number) {
            Category::create(['name' => 'Kategori Uji ' . $number]);
        }
        Category::create(['name' => 'Minuman']);

        $this->get(route('categories.index', ['search' => 'Kategori Uji', 'page' => 2]))
            ->assertOk()
            ->assertSee('Kategori Uji 1')
            ->assertDontSee('Minuman')
            ->assertViewHas('categories', function ($categories) {
                $this->assertSame(2, $categories->currentPage());
                $this->assertStringContainsString('search=Kategori%20Uji', $categories->url(1));
                $this->assertStringContainsString('page=1', $categories->url(1));

                return true;
            });
    }

    public function test_product_search_matches_name_or_code(): void
    {
        $category = Category::create(['name' => 'Makanan']);
        $byCode = $this->createProduct($category, 'PR-SEB-01', 'Menu Pedas');
        $byName = $this->createProduct($category, 'PR-002', 'Seblak Original');
        $unmatched = $this->createProduct($category, 'PR-003', 'Cilok Kuah');

        $this->get(route('products.index', ['search' => 'PR-SEB-01']))
            ->assertOk()
            ->assertSee($byCode->name)
            ->assertDontSee($byName->name);

        $this->get(route('products.index', ['search' => 'Seblak']))
            ->assertOk()
            ->assertSee($byName->name)
            ->assertDontSee($unmatched->name);
    }

    public function test_product_category_and_status_filters_can_be_combined(): void
    {
        $category = Category::create(['name' => 'Makanan']);
        $otherCategory = Category::create(['name' => 'Minuman']);
        $inactive = $this->createProduct($category, 'PR-INACTIVE', 'Produk Nonaktif', false);
        $this->createProduct($category, 'PR-ACTIVE', 'Produk Aktif');
        $this->createProduct($otherCategory, 'PR-OTHER', 'Produk Kategori Lain');

        $this->get(route('products.index', [
            'category_id' => $category->id,
            'status' => 'inactive',
        ]))
            ->assertOk()
            ->assertSee($inactive->name)
            ->assertDontSee('Produk Aktif')
            ->assertDontSee('Produk Kategori Lain')
            ->assertSee('Reset Filter')
            ->assertViewHas('products', fn ($products) => $products->total() === 1);
    }

    public function test_recipes_can_be_filtered_by_product_name_category_and_recipe_status(): void
    {
        $category = Category::create(['name' => 'Makanan']);
        $otherCategory = Category::create(['name' => 'Minuman']);
        $product = $this->createProduct($category, 'PR-RECIPE-01', 'Seblak Uji');
        $otherProduct = $this->createProduct($otherCategory, 'PR-RECIPE-02', 'Es Uji');
        $recipe = Recipe::create([
            'product_id' => $product->id,
            'name' => 'Resep Seblak Uji',
            'is_active' => false,
        ]);
        Recipe::create([
            'product_id' => $otherProduct->id,
            'name' => 'Resep Es Uji',
            'is_active' => false,
        ]);

        $this->get(route('recipes.index', [
            'search' => 'Seblak',
            'category_id' => $category->id,
            'status' => 'inactive',
        ]))
            ->assertOk()
            ->assertSee($recipe->name)
            ->assertDontSee('Resep Es Uji')
            ->assertSee('Reset Filter')
            ->assertViewHas('recipes', fn ($recipes) => $recipes->total() === 1);
    }

    private function createProduct(
        Category $category,
        string $code,
        string $name,
        bool $isActive = true
    ): Product {
        return Product::create([
            'category_id' => $category->id,
            'code' => $code,
            'name' => $name,
            'is_active' => $isActive,
        ]);
    }
}

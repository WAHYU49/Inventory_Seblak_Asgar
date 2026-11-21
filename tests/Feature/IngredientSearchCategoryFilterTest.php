<?php

namespace Tests\Feature;

use App\Models\Ingredient;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class IngredientSearchCategoryFilterTest extends TestCase
{
    use DatabaseTransactions;

    public function test_name_category_and_stock_filters_combine_and_pagination_keeps_them(): void
    {
        $this->actingAs(User::factory()->create());

        $search = 'Search-'.Str::random(8);

        foreach (range(1, 12) as $number) {
            $this->createIngredient(
                $search.' '.$number,
                'Bumbu',
                2,
                true
            );
        }

        $this->createIngredient($search.' Kategori Lain', 'Topping', 2, true);
        $this->createIngredient($search.' Stok Aman', 'Bumbu', 10, true);
        $this->createIngredient($search.' Nonaktif', 'Bumbu', 2, false);

        $this->get(route('ingredients.index', [
            'search' => $search,
            'category' => 'Bumbu',
            'stock_status' => 'low',
            'page' => 2,
        ]))
            ->assertOk()
            ->assertSee($search)
            ->assertSee('Reset Filter')
            ->assertViewHas('ingredients', function ($ingredients) use ($search) {
                $this->assertSame(12, $ingredients->total());
                $this->assertSame(2, $ingredients->currentPage());

                parse_str(parse_url($ingredients->url(1), PHP_URL_QUERY), $query);
                $this->assertSame($search, $query['search']);
                $this->assertSame('Bumbu', $query['category']);
                $this->assertSame('low', $query['stock_status']);
                $this->assertSame('1', (string) $query['page']);

                return true;
            })
            ->assertDontSee($search.' Kategori Lain')
            ->assertDontSee($search.' Stok Aman')
            ->assertDontSee($search.' Nonaktif');
    }

    public function test_category_filter_is_optional_and_empty_categories_are_not_offered(): void
    {
        $this->actingAs(User::factory()->create());
        $bumbu = $this->createIngredient('Bahan Bumbu Uji', 'Bumbu', 0);
        $this->createIngredient('Bahan Tanpa Kategori', null, 0);

        $this->get(route('ingredients.index', ['category' => 'Bumbu']))
            ->assertOk()
            ->assertSee($bumbu->name)
            ->assertDontSee('Bahan Tanpa Kategori')
            ->assertSee('value="Bumbu"', false)
            ->assertSee('selected>', false);
    }

    private function createIngredient(
        string $name,
        ?string $category,
        float $stock,
        bool $active = true
    ): Ingredient {
        return Ingredient::create([
            'code' => 'BB-'.Str::upper(Str::random(12)),
            'name' => $name,
            'category' => $category,
            'unit' => 'gram',
            'current_stock' => $stock,
            'minimum_stock' => 5,
            'is_active' => $active,
        ]);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Ingredient;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class IngredientAutocompleteTest extends TestCase
{
    use DatabaseTransactions;

    public function test_suggestions_match_case_insensitively_and_include_category_and_identity(): void
    {
        $this->actingAs(User::factory()->create());
        $ingredient = $this->createIngredient('Bumbu Cabai Autocomplete', 'Bumbu dapur');

        $this->getJson(route('ingredients.autocomplete', ['q' => 'bUmBu cA']))
            ->assertOk()
            ->assertJsonPath('data.0.id', $ingredient->id)
            ->assertJsonPath('data.0.name', $ingredient->name)
            ->assertJsonPath('data.0.category', 'Bumbu dapur')
            ->assertJsonMissingPath('data.0.current_stock')
            ->assertJsonMissingPath('data.0.purchase_price');
    }

    public function test_no_match_returns_empty_data_and_results_are_limited(): void
    {
        $this->actingAs(User::factory()->create());

        foreach (range(1, 12) as $number) {
            $this->createIngredient('Autocomplete Bumbu '.$number, 'Bumbu');
        }

        $this->getJson(route('ingredients.autocomplete', ['q' => 'not-a-match']))
            ->assertOk()
            ->assertExactJson(['data' => []]);

        $this->getJson(route('ingredients.autocomplete', ['q' => 'autocomplete']))
            ->assertOk()
            ->assertJsonCount(10, 'data');
    }

    public function test_suggestions_respect_existing_category_and_stock_filters(): void
    {
        $this->actingAs(User::factory()->create());
        $matching = $this->createIngredient('Bumbu Autocomplete cocok', 'Rempah', 2);
        $this->createIngredient('Bumbu Autocomplete kategori lain', 'Saus', 2);
        $this->createIngredient('Bumbu Autocomplete stok aman', 'Rempah', 10);

        $this->getJson(route('ingredients.autocomplete', [
            'q' => 'autocomplete',
            'category' => 'Rempah',
            'stock_status' => 'low',
        ]))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matching->id);
    }

    public function test_short_queries_are_rejected(): void
    {
        $this->actingAs(User::factory()->create());

        $this->getJson(route('ingredients.autocomplete', ['q' => 'a']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('q');
    }

    public function test_endpoint_requires_authentication(): void
    {
        $this->get(route('ingredients.autocomplete', ['q' => 'bumbu']))
            ->assertRedirect(route('login'));
    }

    public function test_ingredient_page_keeps_existing_filters_with_autocomplete_enabled(): void
    {
        $this->actingAs(User::factory()->create());
        $ingredient = $this->createIngredient('Bumbu Autocomplete halaman', 'Rempah', 2);

        $this->get(route('ingredients.index', [
            'search' => 'Bumbu',
            'category' => 'Rempah',
            'stock_status' => 'low',
        ]))
            ->assertOk()
            ->assertSee($ingredient->name)
            ->assertSee('id="ingredient-filter-form"', false)
            ->assertSee('data-autocomplete-url="'.route('ingredients.autocomplete').'"', false)
            ->assertSee('id="ingredient-category" name="category"', false)
            ->assertSee('id="stock-status" name="stock_status"', false);
    }

    private function createIngredient(
        string $name,
        ?string $category,
        float $stock = 0
    ): Ingredient {
        return Ingredient::create([
            'code' => 'BB-'.Str::upper(Str::random(12)),
            'name' => $name,
            'category' => $category,
            'unit' => 'gram',
            'current_stock' => $stock,
            'minimum_stock' => 5,
            'is_active' => true,
        ]);
    }
}

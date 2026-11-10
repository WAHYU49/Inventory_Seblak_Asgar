<?php

namespace Tests\Feature;

use App\Models\Ingredient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IngredientCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_ingredient_with_category(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('ingredients.store'), $this->ingredientInput([
                'category' => 'Bumbu dapur',
            ]))
            ->assertRedirect(route('ingredients.index'));

        $ingredient = Ingredient::where('code', 'BB-CATEGORY')->firstOrFail();

        $this->assertSame('Bumbu dapur', $ingredient->category);
        $this->assertDatabaseCount('categories', 0);
    }

    public function test_category_is_shown_on_edit_form_and_can_be_updated(): void
    {
        $ingredient = Ingredient::create($this->ingredientInput([
            'code' => 'BB-EDIT-CATEGORY',
            'category' => 'Bahan lama',
        ]));

        $this->actingAs(User::factory()->create())
            ->get(route('ingredients.edit', $ingredient))
            ->assertOk()
            ->assertSee('name="category"', false)
            ->assertSee('value="Bahan lama"', false);

        $this->put(route('ingredients.update', $ingredient), [
            'code' => $ingredient->code,
            'name' => $ingredient->name,
            'category' => 'Bahan kering',
            'unit' => $ingredient->unit,
            'minimum_stock' => $ingredient->minimum_stock,
        ])->assertRedirect(route('ingredients.index'));

        $this->assertSame('Bahan kering', $ingredient->fresh()->category);
    }

    public function test_ingredient_with_empty_legacy_category_is_displayed_safely(): void
    {
        $ingredient = Ingredient::create($this->ingredientInput([
            'code' => 'BB-EMPTY-CATEGORY',
            'category' => null,
        ]));

        $this->actingAs(User::factory()->create())
            ->get(route('ingredients.index'))
            ->assertOk()
            ->assertSee($ingredient->code)
            ->assertSee('>-</td>', false);

        $this->assertNull($ingredient->fresh()->category);
    }

    public function test_category_longer_than_column_limit_is_rejected(): void
    {
        $this->actingAs(User::factory()->create())
            ->from(route('ingredients.create'))
            ->post(route('ingredients.store'), $this->ingredientInput([
                'category' => str_repeat('a', 256),
            ]))
            ->assertRedirect(route('ingredients.create'))
            ->assertSessionHasErrors('category');

        $this->assertDatabaseCount('ingredients', 0);
    }

    private function ingredientInput(array $overrides = []): array
    {
        return array_merge([
            'code' => 'BB-CATEGORY',
            'name' => 'Bahan Kategori',
            'category' => null,
            'unit' => 'gram',
            'current_stock' => 0,
            'minimum_stock' => 1,
        ], $overrides);
    }
}

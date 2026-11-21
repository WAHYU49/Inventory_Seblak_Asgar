<?php

namespace Tests\Feature;

use App\Models\Ingredient;
use App\Models\PurchaseDetail;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class PurchaseIngredientSearchTest extends TestCase
{
    use DatabaseTransactions;

    public function test_each_purchase_detail_has_search_and_only_active_ingredient_options(): void
    {
        $this->actingAs(User::factory()->create());

        $active = $this->createIngredient('Bahan Aktif Uji');
        $this->createIngredient('Bahan Nonaktif Uji', false);

        $this->withSession([
            '_old_input' => [
                'details' => [
                    ['ingredient_id' => $active->id],
                    ['ingredient_id' => ''],
                ],
            ],
        ])
            ->get(route('purchases.create'))
            ->assertOk()
            ->assertSee('id="purchase-ingredient-search-0"', false)
            ->assertSee('id="purchase-ingredient-search-1"', false)
            ->assertSee('id="purchase-ingredient-0"', false)
            ->assertSee('id="purchase-ingredient-1"', false)
            ->assertSee('data-autocomplete-target="purchase-ingredient-0"', false)
            ->assertSee('data-autocomplete-target="purchase-ingredient-1"', false)
            ->assertSee('data-autocomplete-target="purchase-ingredient-${purchaseIndex}"', false)
            ->assertSee('name="details[0][ingredient_id]"', false)
            ->assertSee('name="details[1][ingredient_id]"', false)
            ->assertSee($active->name)
            ->assertDontSee('Bahan Nonaktif Uji')
            ->assertSee('select.value = String(item.id)', false)
            ->assertSee('document.addEventListener(\'input\'', false);

        $this->assertSame(0.0, (float) $active->fresh()->current_stock);
    }

    public function test_purchase_autocomplete_returns_the_selected_active_ingredient_id_case_insensitively(): void
    {
        $this->actingAs(User::factory()->create());
        $active = $this->createIngredient('Bahan Bumbu Aktif');
        $this->createIngredient('Bahan Bumbu Nonaktif', false);

        $this->getJson(route('ingredients.autocomplete', [
            'q' => 'bUmBu aK',
            'active_only' => 1,
        ]))
            ->assertOk()
            ->assertJsonPath('data.0.id', $active->id)
            ->assertJsonPath('data.0.name', $active->name)
            ->assertJsonMissing(['name' => 'Bahan Bumbu Nonaktif']);
    }

    public function test_purchase_submission_still_uses_selected_ingredient_id_and_updates_stock(): void
    {
        $this->actingAs(User::factory()->create());
        $ingredient = $this->createIngredient('Bahan untuk Pembelian');

        $this->post(route('purchases.store'), [
            'idempotency_key' => (string) Str::uuid(),
            'purchase_date' => '2026-11-21',
            'details' => [
                ['ingredient_id' => $ingredient->id, 'quantity' => 2.5],
            ],
        ])->assertRedirect(route('purchases.index'));

        $this->assertSame(2.5, (float) $ingredient->fresh()->current_stock);
        $this->assertSame(
            $ingredient->id,
            (int) PurchaseDetail::where('ingredient_id', $ingredient->id)->value('ingredient_id')
        );
        $this->assertSame(1, StockMovement::where('ingredient_id', $ingredient->id)->count());
    }

    private function createIngredient(string $name, bool $active = true): Ingredient
    {
        return Ingredient::create([
            'code' => 'BB-'.Str::upper(Str::random(12)),
            'name' => $name,
            'unit' => 'gram',
            'current_stock' => 0,
            'minimum_stock' => 1,
            'is_active' => $active,
        ]);
    }
}

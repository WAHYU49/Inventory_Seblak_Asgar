<?php

namespace Tests\Feature;

use App\Models\Ingredient;
use App\Models\Purchase;
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
            ->assertSee('document.addEventListener(\'input\'', false)
            ->assertViewHas('hasActiveIngredients', true);

        $this->assertSame(0.0, (float) $active->fresh()->current_stock);
    }

    public function test_purchase_form_renders_when_no_active_ingredients_exist(): void
    {
        $this->actingAs(User::factory()->create());
        $this->createIngredient('Bahan Tidak Aktif', false);

        $this->get(route('purchases.create'))
            ->assertOk()
            ->assertSee('Belum ada bahan baku.')
            ->assertSee('data-autocomplete-active-only="true"', false)
            ->assertViewHas('hasActiveIngredients', false)
            ->assertSee('disabled', false);
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

    public function test_purchase_accepts_valid_quantities_with_up_to_three_decimal_places(): void
    {
        $this->actingAs(User::factory()->create());

        foreach ([
            ['1', 1.0],
            ['1.5', 1.5],
            ['1.25', 1.25],
            ['0.125', 0.125],
            ['999999999.999', 999999999.999],
        ] as [$quantity, $expectedStock]) {
            $ingredient = $this->createIngredient('Bahan Kuantitas '.$quantity);
            $ingredient->update(['purchase_price' => 100]);

            $this->post(route('purchases.store'), [
                'idempotency_key' => (string) Str::uuid(),
                'purchase_date' => '2026-11-21',
                'details' => [
                    ['ingredient_id' => $ingredient->id, 'quantity' => $quantity],
                ],
            ])->assertRedirect(route('purchases.index'));

            $detail = PurchaseDetail::where('ingredient_id', $ingredient->id)->firstOrFail();

            $this->assertSame($expectedStock, (float) $ingredient->fresh()->current_stock);
            $this->assertSame($expectedStock, (float) $detail->quantity);
            $this->assertSame($expectedStock * 100, (float) $detail->subtotal);
            $this->assertSame(1, StockMovement::where('ingredient_id', $ingredient->id)->count());
        }
    }

    public function test_purchase_rejects_nonpositive_overprecision_and_out_of_range_quantities(): void
    {
        $this->actingAs(User::factory()->create());
        $ingredient = $this->createIngredient('Bahan Kuantitas Tidak Valid');

        foreach (['0', '-0.001', '0.0001', '1.2345', '1000000000'] as $quantity) {
            $key = (string) Str::uuid();

            $this->from(route('purchases.create'))
                ->post(route('purchases.store'), [
                    'idempotency_key' => $key,
                    'purchase_date' => '2026-11-21',
                    'details' => [
                        ['ingredient_id' => $ingredient->id, 'quantity' => $quantity],
                    ],
                ])
                ->assertRedirect(route('purchases.create'))
                ->assertSessionHasErrors('details.0.quantity');

            $this->assertFalse(Purchase::where('idempotency_key', $key)->exists());
        }

        $this->assertSame(0.0, (float) $ingredient->fresh()->current_stock);
        $this->assertSame(0, PurchaseDetail::where('ingredient_id', $ingredient->id)->count());
        $this->assertSame(0, StockMovement::where('ingredient_id', $ingredient->id)->count());
    }

    public function test_purchase_rejects_quantity_that_would_overflow_current_stock_capacity(): void
    {
        $this->actingAs(User::factory()->create());
        $ingredient = $this->createIngredient(
            'Bahan Stok Maksimum',
            true,
            999999999.999
        );
        $key = (string) Str::uuid();

        $this->from(route('purchases.create'))
            ->post(route('purchases.store'), [
                'idempotency_key' => $key,
                'purchase_date' => '2026-11-21',
                'details' => [
                    ['ingredient_id' => $ingredient->id, 'quantity' => '0.001'],
                ],
            ])
            ->assertRedirect(route('purchases.create'))
            ->assertSessionHasErrors('details');

        $this->assertSame(999999999.999, (float) $ingredient->fresh()->current_stock);
        $this->assertFalse(Purchase::where('idempotency_key', $key)->exists());
        $this->assertSame(0, PurchaseDetail::where('ingredient_id', $ingredient->id)->count());
        $this->assertSame(0, StockMovement::where('ingredient_id', $ingredient->id)->count());
    }

    private function createIngredient(
        string $name,
        bool $active = true,
        float $stock = 0
    ): Ingredient {
        return Ingredient::create([
            'code' => 'BB-'.Str::upper(Str::random(12)),
            'name' => $name,
            'unit' => 'gram',
            'current_stock' => $stock,
            'minimum_stock' => 1,
            'is_active' => $active,
        ]);
    }
}

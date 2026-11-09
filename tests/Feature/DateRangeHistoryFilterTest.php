<?php

namespace Tests\Feature;

use App\Models\Ingredient;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\StockAdjustment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DateRangeHistoryFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_history_filters_purchase_date_and_rejects_reversed_range(): void
    {
        $this->actingAs(User::factory()->create());

        $this->createPurchase('PB-EARLY', '2026-03-01');
        $this->createPurchase('PB-MIDDLE', '2026-03-10');
        $this->createPurchase('PB-LATE', '2026-03-20');

        $this->get(route('purchases.index', ['start_date' => '2026-03-10']))
            ->assertOk()
            ->assertSee('PB-MIDDLE')
            ->assertSee('PB-LATE')
            ->assertDontSee('PB-EARLY');

        $this->get(route('purchases.index', ['end_date' => '2026-03-10']))
            ->assertOk()
            ->assertSee('PB-EARLY')
            ->assertSee('PB-MIDDLE')
            ->assertDontSee('PB-LATE');

        $this->get(route('purchases.index', [
            'start_date' => '2026-03-05',
            'end_date' => '2026-03-15',
        ]))
            ->assertOk()
            ->assertSee('PB-MIDDLE')
            ->assertDontSee('PB-EARLY')
            ->assertDontSee('PB-LATE')
            ->assertSee('value="2026-03-05"', false)
            ->assertSee('value="2026-03-15"', false);

        $this->get(route('purchases.index', [
            'start_date' => '2026-03-20',
            'end_date' => '2026-03-10',
        ]))->assertRedirect()->assertSessionHasErrors('end_date');

        $this->get(route('purchases.index', [
            'start_date' => '10-03-2026',
        ]))->assertRedirect()->assertSessionHasErrors('start_date');
    }

    public function test_sale_history_filters_sale_date_and_rejects_reversed_range(): void
    {
        $this->actingAs(User::factory()->create());

        $this->createSale('PJ-EARLY', '2026-03-01');
        $this->createSale('PJ-MIDDLE', '2026-03-10');
        $this->createSale('PJ-LATE', '2026-03-20');

        $this->get(route('sales.index', ['start_date' => '2026-03-10']))
            ->assertOk()
            ->assertSee('PJ-MIDDLE')
            ->assertSee('PJ-LATE')
            ->assertDontSee('PJ-EARLY');

        $this->get(route('sales.index', ['end_date' => '2026-03-10']))
            ->assertOk()
            ->assertSee('PJ-EARLY')
            ->assertSee('PJ-MIDDLE')
            ->assertDontSee('PJ-LATE');

        $this->get(route('sales.index', [
            'start_date' => '2026-03-05',
            'end_date' => '2026-03-15',
        ]))
            ->assertOk()
            ->assertSee('PJ-MIDDLE')
            ->assertDontSee('PJ-EARLY')
            ->assertDontSee('PJ-LATE')
            ->assertSee('value="2026-03-05"', false)
            ->assertSee('value="2026-03-15"', false);

        $this->get(route('sales.index', [
            'start_date' => '2026-03-20',
            'end_date' => '2026-03-10',
        ]))->assertRedirect()->assertSessionHasErrors('end_date');

        $this->get(route('sales.index', [
            'start_date' => '10-03-2026',
        ]))->assertRedirect()->assertSessionHasErrors('start_date');
    }

    public function test_adjustment_history_filters_adjustment_date_and_rejects_reversed_range(): void
    {
        $this->actingAs(User::factory()->create());

        $this->createAdjustment('SA-EARLY', '2026-03-01', 'BB-EARLY');
        $this->createAdjustment('SA-MIDDLE', '2026-03-10', 'BB-MIDDLE');
        $this->createAdjustment('SA-LATE', '2026-03-20', 'BB-LATE');

        $this->get(route('stock-adjustments.index', ['start_date' => '2026-03-10']))
            ->assertOk()
            ->assertSee('SA-MIDDLE')
            ->assertSee('SA-LATE')
            ->assertDontSee('SA-EARLY');

        $this->get(route('stock-adjustments.index', ['end_date' => '2026-03-10']))
            ->assertOk()
            ->assertSee('SA-EARLY')
            ->assertSee('SA-MIDDLE')
            ->assertDontSee('SA-LATE');

        $this->get(route('stock-adjustments.index', [
            'start_date' => '2026-03-05',
            'end_date' => '2026-03-15',
        ]))
            ->assertOk()
            ->assertSee('SA-MIDDLE')
            ->assertDontSee('SA-EARLY')
            ->assertDontSee('SA-LATE')
            ->assertSee('value="2026-03-05"', false)
            ->assertSee('value="2026-03-15"', false);

        $this->get(route('stock-adjustments.index', [
            'start_date' => '2026-03-20',
            'end_date' => '2026-03-10',
        ]))->assertRedirect()->assertSessionHasErrors('end_date');

        $this->get(route('stock-adjustments.index', [
            'start_date' => '10-03-2026',
        ]))->assertRedirect()->assertSessionHasErrors('start_date');
    }

    public function test_purchase_pagination_preserves_date_filters_and_filtering_does_not_change_stock(): void
    {
        $this->actingAs(User::factory()->create());

        for ($index = 1; $index <= 11; $index++) {
            $this->createPurchase(
                'PB-PAGE-' . $index,
                sprintf('2026-03-%02d', $index)
            );

            $this->createSale(
                'PJ-PAGE-' . $index,
                sprintf('2026-03-%02d', $index)
            );

            $this->createAdjustment(
                'SA-PAGE-' . $index,
                sprintf('2026-03-%02d', $index),
                'BB-PAGE-' . $index
            );
        }

        $ingredient = Ingredient::create([
            'code' => 'BB-STOCK',
            'name' => 'Bahan Stok',
            'unit' => 'pcs',
            'current_stock' => 17.5,
            'minimum_stock' => 2,
        ]);

        $response = $this->get(route('purchases.index', [
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-31',
        ]));

        $response->assertOk()
            ->assertSee('start_date=2026-03-01')
            ->assertSee('end_date=2026-03-31')
            ->assertSee('page=2');

        $this->get(route('purchases.index', [
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-31',
            'page' => 2,
        ]))->assertOk();

        $saleResponse = $this->get(route('sales.index', [
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-31',
        ]));

        $saleResponse->assertOk()
            ->assertSee('start_date=2026-03-01')
            ->assertSee('end_date=2026-03-31')
            ->assertSee('page=2');

        $this->get(route('sales.index', [
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-31',
            'page' => 2,
        ]))->assertOk();

        $adjustmentResponse = $this->get(route('stock-adjustments.index', [
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-31',
        ]));

        $adjustmentResponse->assertOk()
            ->assertSee('start_date=2026-03-01')
            ->assertSee('end_date=2026-03-31')
            ->assertSee('page=2');

        $this->get(route('stock-adjustments.index', [
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-31',
            'page' => 2,
        ]))->assertOk();

        $this->assertSame(17.5, (float) $ingredient->fresh()->current_stock);
        $this->assertDatabaseCount('purchases', 11);
        $this->assertDatabaseCount('sales', 11);
        $this->assertDatabaseCount('stock_adjustments', 11);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    private function createPurchase(string $number, string $date): void
    {
        Purchase::create([
            'purchase_number' => $number,
            'purchase_date' => $date,
            'total_amount' => 0,
        ]);
    }

    private function createSale(string $number, string $date): void
    {
        Sale::create([
            'sale_number' => $number,
            'sale_date' => $date,
            'total_amount' => 0,
        ]);
    }

    private function createAdjustment(
        string $number,
        string $date,
        string $ingredientCode
    ): void {
        $ingredient = Ingredient::create([
            'code' => $ingredientCode,
            'name' => $ingredientCode,
            'unit' => 'pcs',
            'current_stock' => 10,
            'minimum_stock' => 2,
        ]);

        StockAdjustment::create([
            'adjustment_number' => $number,
            'ingredient_id' => $ingredient->id,
            'type' => 'increase',
            'quantity' => 1,
            'stock_before' => 9,
            'stock_after' => 10,
            'reason' => 'Stok opname',
            'adjustment_date' => $date,
        ]);
    }
}

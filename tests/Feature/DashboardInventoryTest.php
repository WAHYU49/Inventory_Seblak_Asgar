<?php

namespace Tests\Feature;

use App\Models\Ingredient;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\StockAdjustment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardInventoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_requires_authentication(): void
    {
        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_dashboard_shows_all_activity_counts_and_stock_snapshot_without_prices(): void
    {
        $admin = User::factory()->create();
        $ingredient = $this->createIngredient('BB-DASH', 'Bahan Dashboard', 2, 5);
        $product = $this->createProduct('PR-DASH', 'Produk Dashboard', 123456);

        $this->createPurchase('PB-DASH-1', '2026-03-01', $ingredient, '234567');
        $this->createPurchase('PB-DASH-2', '2026-03-20', $ingredient, '234567');
        $this->createSale('PJ-DASH-1', '2026-03-01', $product);
        $this->createSale('PJ-DASH-2', '2026-03-20', $product);
        $this->createAdjustment('SA-DASH-1', '2026-03-01', $ingredient);
        $this->createAdjustment('SA-DASH-2', '2026-03-20', $ingredient);

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk()
            ->assertSee('Pembelian (2 transaksi)')
            ->assertSee('Penjualan (2 transaksi)')
            ->assertSee('Penyesuaian Stok (2 aktivitas)')
            ->assertSee('Total Bahan Baku Aktif')
            ->assertSee(route('products.index'))
            ->assertSee(route('ingredients.index'))
            ->assertSee(route('recipes.index'))
            ->assertSee(route('ingredients.index', ['stock_status' => 'empty']))
            ->assertSee(route('ingredients.index', ['stock_status' => 'low']))
            ->assertSee(route('ingredients.index', ['stock_status' => 'safe']))
            ->assertSee(route('purchases.index'))
            ->assertSee(route('sales.index'))
            ->assertSee(route('stock-adjustments.index'))
            ->assertSee('Bahan Dashboard')
            ->assertSee('Produk Dashboard')
            ->assertDontSee('Rp')
            ->assertDontSee('123456')
            ->assertDontSee('234567');
    }

    public function test_dashboard_activity_counts_use_transaction_dates_but_stock_status_is_not_filtered(): void
    {
        $this->actingAs(User::factory()->create());

        $ingredient = $this->createIngredient('BB-PERIOD', 'Bahan Periode', 0, 3);
        $this->createIngredient('BB-LOW-PERIOD', 'Bahan Menipis Periode', 2, 3);
        $this->createIngredient('BB-SAFE-PERIOD', 'Bahan Aman Periode', 5, 3);
        $product = $this->createProduct('PR-PERIOD', 'Produk Periode', 0);

        foreach ([
            ['2026-03-01', 'EARLY'],
            ['2026-03-10', 'MIDDLE'],
            ['2026-03-20', 'LATE'],
        ] as [$date, $suffix]) {
            $this->createPurchase('PB-' . $suffix, $date, $ingredient, 0);
            $this->createSale('PJ-' . $suffix, $date, $product);
            $this->createAdjustment('SA-' . $suffix, $date, $ingredient);
        }

        $startDateResponse = $this->get(route('dashboard', ['start_date' => '2026-03-10']));
        $startDateResponse
            ->assertOk()
            ->assertSee('Pembelian (2 transaksi)')
            ->assertSee('Penjualan (2 transaksi)')
            ->assertSee('Penyesuaian Stok (2 aktivitas)')
            ->assertSee('value="2026-03-10"', false)
            ->assertSee(route('dashboard'))
            ->assertSee('PB-MIDDLE')
            ->assertDontSee('PB-EARLY');

        foreach (['#c0392b', '#e67e22', '#27834c'] as $stockColor) {
            $this->assertSame(
                1,
                preg_match(
                    '/<div class="number" style="color: ' . preg_quote($stockColor, '/') . ';">\s*1\s*<\/div>/',
                    $startDateResponse->getContent()
                )
            );
        }

        $this->get(route('dashboard', ['end_date' => '2026-03-10']))
            ->assertOk()
            ->assertSee('Pembelian (2 transaksi)')
            ->assertSee('Penjualan (2 transaksi)')
            ->assertSee('Penyesuaian Stok (2 aktivitas)')
            ->assertSee('PB-EARLY')
            ->assertDontSee('PB-LATE');

        $this->get(route('dashboard', [
            'start_date' => '2026-03-05',
            'end_date' => '2026-03-15',
        ]))
            ->assertOk()
            ->assertSee('Pembelian (1 transaksi)')
            ->assertSee('Penjualan (1 transaksi)')
            ->assertSee('Penyesuaian Stok (1 aktivitas)')
            ->assertSee('PB-MIDDLE')
            ->assertDontSee('PB-EARLY')
            ->assertDontSee('PB-LATE')
            ->assertSee('value="2026-03-05"', false)
            ->assertSee('value="2026-03-15"', false);

        $this->get(route('dashboard', [
            'start_date' => '2026-03-20',
            'end_date' => '2026-03-10',
        ]))->assertRedirect()->assertSessionHasErrors('end_date');

        $this->get(route('dashboard', [
            'start_date' => '20-03-2026',
        ]))->assertRedirect()->assertSessionHasErrors('start_date');

        $this->assertSame(0.0, (float) $ingredient->fresh()->current_stock);
        $this->assertDatabaseCount('purchases', 3);
        $this->assertDatabaseCount('sales', 3);
        $this->assertDatabaseCount('stock_adjustments', 3);
    }

    public function test_dashboard_limits_each_activity_history_to_ten_records(): void
    {
        $this->actingAs(User::factory()->create());

        $ingredient = $this->createIngredient('BB-LIMIT', 'Bahan Batas', 10, 2);
        $product = $this->createProduct('PR-LIMIT', 'Produk Batas', 0);

        for ($index = 1; $index <= 11; $index++) {
            $suffix = sprintf('%02d', $index);
            $this->createPurchase('PB-DASH-' . $suffix, '2026-03-10', $ingredient, 0);
            $this->createSale('PJ-DASH-' . $suffix, '2026-03-10', $product);
            $this->createAdjustment('SA-DASH-' . $suffix, '2026-03-10', $ingredient);
        }

        $content = $this->get(route('dashboard'))->assertOk()->getContent();

        $this->assertSame(10, substr_count($content, 'PB-DASH-'));
        $this->assertSame(10, substr_count($content, 'PJ-DASH-'));
        $this->assertSame(10, substr_count($content, 'SA-DASH-'));
        $this->assertStringContainsString('Pembelian (11 transaksi)', $content);
        $this->assertStringContainsString('Penjualan (11 transaksi)', $content);
        $this->assertStringContainsString('Penyesuaian Stok (11 aktivitas)', $content);
    }

    private function createIngredient(
        string $code,
        string $name,
        float $stock,
        float $minimum
    ): Ingredient {
        return Ingredient::create([
            'code' => $code,
            'name' => $name,
            'unit' => 'pcs',
            'current_stock' => $stock,
            'minimum_stock' => $minimum,
            'purchase_price' => 99999,
            'is_active' => true,
        ]);
    }

    private function createProduct(string $code, string $name, float $price): Product
    {
        return Product::create([
            'code' => $code,
            'name' => $name,
            'selling_price' => $price,
            'is_active' => true,
        ]);
    }

    private function createPurchase(
        string $number,
        string $date,
        Ingredient $ingredient,
        float|string $unitPrice
    ): void {
        $purchase = Purchase::create([
            'purchase_number' => $number,
            'purchase_date' => $date,
            'total_amount' => 0,
        ]);

        PurchaseDetail::create([
            'purchase_id' => $purchase->id,
            'ingredient_id' => $ingredient->id,
            'quantity' => 2,
            'unit_price' => $unitPrice,
            'subtotal' => 2 * (float) $unitPrice,
        ]);
    }

    private function createSale(string $number, string $date, Product $product): void
    {
        $sale = Sale::create([
            'sale_number' => $number,
            'sale_date' => $date,
            'total_amount' => 0,
        ]);

        SaleDetail::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => $product->selling_price,
            'subtotal' => 0,
        ]);
    }

    private function createAdjustment(
        string $number,
        string $date,
        Ingredient $ingredient
    ): void {
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

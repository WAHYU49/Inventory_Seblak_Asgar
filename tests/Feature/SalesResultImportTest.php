<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\RecipeDetail;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

class SalesResultImportTest extends TestCase
{
    use DatabaseTransactions;

    public function test_preview_aggregates_duplicate_products_without_changing_stock_or_showing_prices(): void
    {
        $this->actingAs(User::factory()->create());
        [$product, $ingredient] = $this->createProductWithIngredient('Seblak Original', 1, 20);
        $product->update(['selling_price' => 987654]);

        $preview = $this->preview([
            $this->headers(),
            ['Seblak Original', 5],
            [' seblak original ', 3],
        ]);

        $preview->assertOk()
            ->assertSee('Seblak Original')
            ->assertSee('Total hasil penggabungan')
            ->assertSee('8')
            ->assertDontSee('987654')
            ->assertDontSee('Harga');

        $visibleText = preg_replace('/\s+/', ' ', strip_tags($preview->getContent()));
        $this->assertStringContainsString('5 + 3', $visibleText);
        $this->assertSame(20.0, (float) $ingredient->fresh()->current_stock);
        $this->assertSame(0, Sale::where('notes', 'Import hasil penjualan Excel.')->count());
        $this->assertSame(0, StockMovement::where('ingredient_id', $ingredient->id)->count());
    }

    public function test_invalid_rows_are_reported_and_preview_cannot_be_confirmed(): void
    {
        $this->actingAs(User::factory()->create());
        [$product, $ingredient] = $this->createProductWithIngredient('Menu Valid', 1, 10);

        $this->preview([
            $this->headers(),
            ['Menu Tidak Ada', 2],
            [$product->name, 0],
            [$product->name, -1],
        ])
            ->assertOk()
            ->assertSee('Baris 2: Produk tidak ditemukan.')
            ->assertSee('Baris 3: Terjual harus berupa angka positif.')
            ->assertSee('Baris 4: Terjual harus berupa angka positif.')
            ->assertSee('Import tidak dapat dikonfirmasi')
            ->assertDontSee('name="token"', false);

        $this->assertSame(10.0, (float) $ingredient->fresh()->current_stock);
        $this->assertSame(0, Sale::where('notes', 'Import hasil penjualan Excel.')->count());
    }

    public function test_confirmation_processes_aggregated_sale_once_and_repeated_submit_is_idempotent(): void
    {
        $this->actingAs(User::factory()->create());
        [$product, $ingredient] = $this->createProductWithIngredient('Menu Digabung', 1, 10);
        $beforeSales = Sale::count();

        $preview = $this->preview([
            $this->headers(),
            [$product->name, 5],
            [$product->name, 3],
        ]);
        $token = $this->tokenFrom($preview);

        $this->post(route('sales.import.commit'), ['token' => $token])
            ->assertRedirect(route('sales.index'));

        $sale = Sale::where('notes', 'Import hasil penjualan Excel.')
            ->latest('id')
            ->firstOrFail();
        $detail = SaleDetail::where('sale_id', $sale->id)->firstOrFail();

        $this->assertSame(8.0, (float) $detail->quantity);
        $this->assertSame(2.0, (float) $ingredient->fresh()->current_stock);
        $this->assertSame($beforeSales + 1, Sale::count());
        $this->assertSame(1, StockMovement::where('ingredient_id', $ingredient->id)->count());
        $this->assertNotNull($sale->idempotency_key);

        $this->post(route('sales.import.commit'), ['token' => $token])
            ->assertRedirect(route('sales.index'))
            ->assertSessionHas('success', 'Import ini sudah diproses; tidak ada transaksi tambahan.');

        $this->assertSame(2.0, (float) $ingredient->fresh()->current_stock);
        $this->assertSame($beforeSales + 1, Sale::count());
        $this->assertSame(1, StockMovement::where('ingredient_id', $ingredient->id)->count());
    }

    public function test_confirmation_rolls_back_all_products_when_one_product_lacks_sufficient_stock(): void
    {
        $this->actingAs(User::factory()->create());
        [$firstProduct, $firstIngredient] = $this->createProductWithIngredient('Menu Cukup', 1, 10);
        [$secondProduct, $secondIngredient] = $this->createProductWithIngredient('Menu Kurang', 1, 2);
        $beforeSales = Sale::count();

        $preview = $this->preview([
            $this->headers(),
            [$firstProduct->name, 2],
            [$secondProduct->name, 3],
        ]);
        $token = $this->tokenFrom($preview);

        $this->from(route('sales.import.preview.show', ['token' => $token]))
            ->post(route('sales.import.commit'), ['token' => $token])
            ->assertRedirect(route('sales.import.preview.show', ['token' => $token]))
            ->assertSessionHasErrors('details');

        $this->assertSame(10.0, (float) $firstIngredient->fresh()->current_stock);
        $this->assertSame(2.0, (float) $secondIngredient->fresh()->current_stock);
        $this->assertSame($beforeSales, Sale::count());
        $this->assertSame(0, StockMovement::whereIn(
            'ingredient_id',
            [$firstIngredient->id, $secondIngredient->id]
        )->count());
    }

    public function test_duplicate_catalog_names_are_rejected_as_ambiguous(): void
    {
        $this->actingAs(User::factory()->create());
        $this->createProductWithIngredient('Nama Sama', 1, 10);
        $this->createProductWithIngredient('Nama Sama', 1, 10);

        $this->preview([
            $this->headers(),
            ['Nama Sama', 1],
        ])
            ->assertOk()
            ->assertSee('Baris 2: Nama produk cocok dengan lebih dari satu produk terdaftar.')
            ->assertDontSee('name="token"', false);
    }

    public function test_manual_sale_still_uses_the_existing_stock_transaction_path(): void
    {
        $this->actingAs(User::factory()->create());
        [$product, $ingredient] = $this->createProductWithIngredient('Penjualan Manual', 2, 10);

        $this->post(route('sales.store'), [
            'idempotency_key' => (string) Str::uuid(),
            'sale_date' => '2026-11-21',
            'details' => [
                ['product_id' => $product->id, 'quantity' => 2],
            ],
        ])->assertRedirect(route('sales.index'));

        $this->assertSame(6.0, (float) $ingredient->fresh()->current_stock);
        $this->assertSame(1, SaleDetail::where('product_id', $product->id)->count());
        $this->assertSame(1, StockMovement::where('ingredient_id', $ingredient->id)->count());
    }

    private function preview(array $rows)
    {
        $response = $this->post(route('sales.import.preview'), [
            'sale_date' => '2026-11-21',
            'file' => $this->makeWorkbook($rows),
        ]);

        $response->assertRedirect();

        return $this->get($response->headers->get('Location'));
    }

    private function tokenFrom($response): string
    {
        preg_match(
            '/name="token" value="([A-Za-z0-9]{40})"/',
            $response->getContent(),
            $matches
        );

        $this->assertNotEmpty($matches[1] ?? null);

        return $matches[1];
    }

    private function headers(): array
    {
        return ['Produk', 'Terjual'];
    }

    private function makeWorkbook(array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray($rows, null, 'A1');
        $path = tempnam(sys_get_temp_dir(), 'sales-result-');

        try {
            IOFactory::createWriter($spreadsheet, 'Xlsx')->save($path);
            $content = file_get_contents($path);
        } finally {
            unlink($path);
            $spreadsheet->disconnectWorksheets();
        }

        return UploadedFile::fake()->createWithContent('sales.xlsx', $content);
    }

    private function createProductWithIngredient(
        string $name,
        float $recipeQuantity,
        float $stock
    ): array {
        $ingredient = Ingredient::create([
            'code' => 'BB-'.Str::upper(Str::random(12)),
            'name' => 'Bahan '.Str::upper(Str::random(8)),
            'unit' => 'gram',
            'current_stock' => $stock,
            'minimum_stock' => 1,
            'is_active' => true,
        ]);
        $category = Category::firstOrCreate(['name' => 'Kategori Uji']);
        $product = Product::create([
            'category_id' => $category->id,
            'code' => 'PR-'.Str::upper(Str::random(12)),
            'name' => $name,
            'selling_price' => 987654,
            'is_active' => true,
        ]);
        $recipe = Recipe::create([
            'product_id' => $product->id,
            'name' => 'Resep Uji',
            'is_active' => true,
        ]);
        RecipeDetail::create([
            'recipe_id' => $recipe->id,
            'ingredient_id' => $ingredient->id,
            'quantity' => $recipeQuantity,
        ]);

        return [$product, $ingredient];
    }
}

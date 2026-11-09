<?php

namespace Tests\Feature;

use App\Models\Ingredient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

class IngredientMasterImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_preview_and_import_master_rows_without_changing_stock(): void
    {
        $admin = User::factory()->create();

        $existing = Ingredient::create([
            'code' => 'BB001',
            'name' => 'Nama Lama',
            'unit' => 'gram',
            'current_stock' => 12.5,
            'minimum_stock' => 3,
            'purchase_price' => 1800,
            'is_active' => false,
            'category' => 'Kategori Lama',
            'package_quantity' => 10,
            'het_price' => 2500,
            'hpp_price' => 2200,
            'supplier' => 'Supplier Lama',
            'allocation' => 'Alokasi Lama',
        ]);

        $upload = $this->makeWorkbook([
            $this->headers(),
            [1, 'BB001', 'Kerupuk Baru', 'Snack', 'gram', 12, 3000, null, 'Pemasok A', 'Dapur'],
            [2, 'BB002', 'Minyak', 'Bumbu', 'ml', 1, 18000, 15000, 'Pemasok B', 'Dapur'],
            [null, null, null, null, null, null, null, null, null, null],
            [3, 'BB003', '', 'Bumbu', 'gram', 1, 1000, null, '', ''],
        ]);

        $this->actingAs($admin);

        $previewResponse = $this->post(route('ingredients.import.preview'), [
            'file' => $upload,
        ]);

        $previewResponse->assertOk()
            ->assertSee('Data valid baru: 1')
            ->assertSee('Data valid diperbarui: 1')
            ->assertSee('Baris kosong dilewati: 1')
            ->assertSee('Baris 5: Nama Barang wajib diisi.')
            ->assertDontSee('HET')
            ->assertDontSee('HPP')
            ->assertDontSee('3000')
            ->assertDontSee('15000');

        $this->assertSame(12.5, (float) $existing->fresh()->current_stock);
        $this->assertDatabaseCount('ingredients', 1);
        $this->assertDatabaseCount('stock_movements', 0);

        preg_match('/name="token" value="([^"]+)"/', $previewResponse->getContent(), $matches);
        $this->assertNotEmpty($matches[1] ?? null);

        $this->post(route('ingredients.import.commit'), [
            'token' => $matches[1],
        ])->assertRedirect(route('ingredients.import'))
            ->assertSessionHas('import_report.created', 1)
            ->assertSessionHas('import_report.updated', 1)
            ->assertSessionHas('import_report.skipped', 1)
            ->assertSessionHas('import_report.failed', 1);

        $existing->refresh();
        $this->assertSame('Kerupuk Baru', $existing->name);
        $this->assertSame('Snack', $existing->category);
        $this->assertSame('gram', $existing->unit);
        $this->assertSame(12.5, (float) $existing->current_stock);
        $this->assertSame(3.0, (float) $existing->minimum_stock);
        $this->assertSame(1800.0, (float) $existing->purchase_price);
        $this->assertSame(12.0, (float) $existing->package_quantity);
        $this->assertSame(3000.0, (float) $existing->het_price);
        $this->assertSame(2200.0, (float) $existing->hpp_price);
        $this->assertSame('Pemasok A', $existing->supplier);
        $this->assertSame('Dapur', $existing->allocation);
        $this->assertFalse($existing->is_active);

        $newIngredient = Ingredient::where('code', 'BB002')->firstOrFail();
        $this->assertSame(0.0, (float) $newIngredient->current_stock);
        $this->assertSame('ml', $newIngredient->unit);
        $this->assertSame(18000.0, (float) $newIngredient->het_price);
        $this->assertSame(15000.0, (float) $newIngredient->hpp_price);
        $this->assertSame(0.0, (float) $newIngredient->purchase_price);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_admin_cannot_import_a_file_with_missing_required_headers(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('ingredients.import.preview'), [
                'file' => $this->makeWorkbook([
                    ['SKU', 'Nama Barang'],
                    ['BB001', 'Kerupuk'],
                ]),
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('ingredients', 0);
    }

    public function test_price_headers_are_optional_for_inventory_master_import(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('ingredients.import.preview'), [
                'file' => $this->makeWorkbook([
                    ['SKU ID', 'Nama Barang', 'Kategori', 'Satuan', 'Jumlah / Isi', 'supplier', 'Alokasi'],
                    ['BB001', 'Kerupuk', 'Snack', 'gram', 1, 'Pemasok A', 'Dapur'],
                ]),
            ])
            ->assertOk()
            ->assertSee('Kerupuk')
            ->assertDontSee('HET')
            ->assertDontSee('HPP');
    }

    public function test_import_rejects_a_unit_change_for_an_existing_ingredient(): void
    {
        $ingredient = Ingredient::create([
            'code' => 'BB001',
            'name' => 'Kerupuk',
            'unit' => 'gram',
            'current_stock' => 8,
            'minimum_stock' => 2,
            'purchase_price' => 1000,
        ]);

        $this->actingAs(User::factory()->create())
            ->post(route('ingredients.import.preview'), [
                'file' => $this->makeWorkbook([
                    $this->headers(),
                    [1, 'BB001', 'Kerupuk', 'Snack', 'pcs', 1, 2000, null, '', ''],
                ]),
            ])
            ->assertOk()
            ->assertSee('Satuan berbeda dari data yang sudah tersimpan.')
            ->assertSee('Baris gagal: 1')
            ->assertDontSee('name="token"');

        $ingredient->refresh();
        $this->assertSame('gram', $ingredient->unit);
        $this->assertSame(8.0, (float) $ingredient->current_stock);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_import_pages_require_authentication(): void
    {
        $this->get(route('ingredients.import'))
            ->assertRedirect(route('login'));
    }

    private function headers(): array
    {
        return [
            'No.',
            'SKU ID',
            'Nama Barang',
            'Kategori',
            'Satuan',
            'Jumlah / Isi',
            'HET',
            'HPP satuan',
            'supplier',
            'Alokasi',
        ];
    }

    private function makeWorkbook(array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet();

        // Samakan nama worksheet dengan workbook asli.
        $spreadsheet->getActiveSheet()->setTitle('MASTER BAHAN BAKU');

        $spreadsheet->getActiveSheet()->fromArray($rows, null, 'A1');

        $path = tempnam(sys_get_temp_dir(), 'ingredient-master-');

        try {
            IOFactory::createWriter($spreadsheet, 'Xlsx')->save($path);
            $content = file_get_contents($path);
        } finally {
            unlink($path);
            $spreadsheet->disconnectWorksheets();
        }

        return UploadedFile::fake()->createWithContent('master.xlsx', $content);
    }
}

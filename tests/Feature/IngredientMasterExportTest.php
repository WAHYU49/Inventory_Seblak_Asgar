<?php

namespace Tests\Feature;

use App\Models\Ingredient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class IngredientMasterExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_download_master_excel_with_headers_and_blank_optional_values(): void
    {
        $ingredient = Ingredient::create([
            'code' => 'BB001',
            'name' => 'Kerupuk Seblak',
            'unit' => 'gram',
            'current_stock' => 12.5,
            'minimum_stock' => 3,
            'purchase_price' => 1800,
            'is_active' => true,
            'category' => null,
            'package_quantity' => null,
            'het_price' => null,
            'hpp_price' => null,
            'supplier' => null,
            'allocation' => null,
            'purchase_unit' => null,
        ]);

        $this->actingAs(User::factory()->create());

        $response = $this->get(route('ingredients.export'));

        $response->assertOk();
        $this->assertMatchesRegularExpression(
            '/attachment; filename=master_bahan_baku_\d{8}_\d{6}\.xlsx/',
            $response->headers->get('content-disposition')
        );
        $this->assertStringContainsString(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $response->headers->get('content-type')
        );

        $file = $response->baseResponse->getFile()->getPathname();
        $spreadsheet = IOFactory::load($file);

        try {
            $sheet = $spreadsheet->getActiveSheet();
            $this->assertSame([
                'SKU ID',
                'Nama Barang',
                'Kategori',
                'Satuan',
                'Jumlah / Isi',
                'Supplier',
                'Alokasi',
                'Satuan Pembelian',
                'Stok Saat Ini',
                'Minimum Stok',
                'Status Aktif',
            ], $sheet->rangeToArray('A1:K1', null, false, false)[0]);

            $this->assertSame('BB001', $sheet->getCell('A2')->getValue());
            $this->assertSame('Kerupuk Seblak', $sheet->getCell('B2')->getValue());
            $this->assertNull($sheet->getCell('C2')->getValue());
            $this->assertSame('gram', $sheet->getCell('D2')->getValue());
            $this->assertNull($sheet->getCell('E2')->getValue());
            $this->assertNull($sheet->getCell('F2')->getValue());
            $this->assertNull($sheet->getCell('G2')->getValue());
            $this->assertNull($sheet->getCell('H2')->getValue());
            $this->assertSame(12.5, (float) $sheet->getCell('I2')->getValue());
            $this->assertSame(3.0, (float) $sheet->getCell('J2')->getValue());
            $this->assertSame('Aktif', $sheet->getCell('K2')->getValue());
        } finally {
            $spreadsheet->disconnectWorksheets();
        }

        $ingredient->refresh();
        $this->assertSame(12.5, (float) $ingredient->current_stock);
        $this->assertSame(3.0, (float) $ingredient->minimum_stock);
        $this->assertSame(1800.0, (float) $ingredient->purchase_price);
        $this->assertDatabaseCount('purchases', 0);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_export_route_requires_authentication(): void
    {
        $this->get(route('ingredients.export'))
            ->assertRedirect(route('login'));
    }
}

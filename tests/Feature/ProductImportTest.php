<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

class ProductImportTest extends TestCase
{
    use DatabaseTransactions;

    public function test_valid_product_rows_are_imported_only_to_products_with_unique_codes(): void
    {
        $category = Category::create(['name' => 'Makanan']);
        $ingredient = Ingredient::create([
            'code' => 'BB-IMPORT-PRODUCT',
            'name' => 'Bakso Aci',
            'unit' => 'pcs',
            'current_stock' => 3,
            'minimum_stock' => 1,
        ]);
        $this->actingAs(User::factory()->create());

        $preview = $this->post(route('products.import.preview'), [
            'file' => $this->makeWorkbook([
                [' Kategori ', ' Produk '],
                ['  MAKANAN  ', 'Bakso Aci'],
                ['Makanan', 'Seblak Original'],
            ]),
        ]);

        $preview->assertOk()
            ->assertSee('Data yang akan diimport: 2')
            ->assertSee('Bakso Aci')
            ->assertDontSee('Harga');
        $this->assertDatabaseCount('products', 0);

        $token = $this->tokenFrom($preview);
        $this->post(route('products.import.commit'), ['token' => $token])
            ->assertRedirect(route('products.import'))
            ->assertSessionHas('import_report.created', 2);

        $products = Product::orderBy('id')->get();
        $this->assertCount(2, $products);
        $this->assertSame(['Bakso Aci', 'Seblak Original'], $products->pluck('name')->all());
        $this->assertSame([$category->id, $category->id], $products->pluck('category_id')->all());
        $this->assertSame(2, $products->pluck('code')->unique()->count());
        $this->assertTrue($products->every(fn (Product $product) => str_starts_with($product->code, 'PRD-')));
        $this->assertSame('0.00', $products->first()->selling_price);
        $this->assertSame('Bakso Aci', $ingredient->fresh()->name);
        $this->assertDatabaseCount('ingredients', 1);
    }

    public function test_existing_and_in_file_duplicate_product_names_block_confirmation(): void
    {
        $this->createCategory();
        $existing = $this->createProduct('Produk Lama');

        $preview = $this->actingAs(User::factory()->create())
            ->post(route('products.import.preview'), [
                'file' => $this->makeWorkbook([
                    ['Produk', 'Kategori'],
                    [' produk lama ', 'Menu'],
                    ['Produk Baru', 'Menu'],
                    ['  produk   baru ', 'Menu'],
                ]),
            ]);

        $preview->assertOk()
            ->assertSee('Data duplikat: 2')
            ->assertSee('Nama produk sudah ada di database.')
            ->assertSee('Nama produk duplikat di dalam file')
            ->assertDontSee('name="token"');

        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseHas('products', ['id' => $existing->id, 'name' => 'Produk Lama']);
    }

    public function test_unknown_category_and_empty_product_name_are_reported_without_creating_data(): void
    {
        $this->createCategory();
        $preview = $this->actingAs(User::factory()->create())
            ->post(route('products.import.preview'), [
                'file' => $this->makeWorkbook([
                    ['Produk', 'Kategori'],
                    ['Bakso Aci', 'Kategori Belum Ada'],
                    ['', 'Menu'],
                ]),
            ]);

        $preview->assertOk()
            ->assertSee('Nama produk wajib diisi.')
            ->assertSee('Kategori Excel belum dipetakan ke kategori database.')
            ->assertSee('Data bermasalah: 2')
            ->assertDontSee('Konfirmasi Import');

        $this->assertDatabaseCount('products', 0);
    }

    public function test_unmatched_excel_categories_can_be_mapped_and_are_used_on_import(): void
    {
        $food = Category::create(['name' => 'Makanan']);
        $drink = Category::create(['name' => 'Minuman']);
        $this->actingAs(User::factory()->create());

        $preview = $this->post(route('products.import.preview'), [
            'file' => $this->makeWorkbook([
                ['Produk', 'Kategori'],
                ['Bakso Aci', 'Menu Kuah'],
                ['Es Teh', 'Minuman Dingin'],
            ]),
        ]);

        $preview->assertOk()
            ->assertSee('Kategori Excel: Menu Kuah')
            ->assertSee('Kategori Excel: Minuman Dingin')
            ->assertSee('Kategori Excel belum dipetakan ke kategori database.')
            ->assertSee('Data valid: 0')
            ->assertSee('Data bermasalah: 2')
            ->assertDontSee('Konfirmasi Import');
        $this->assertDatabaseCount('products', 0);
        $this->assertDatabaseCount('categories', 2);

        preg_match_all(
            '/name="mappings\[([a-f0-9]{64})\]"/',
            $preview->getContent(),
            $matches
        );
        $this->assertCount(2, $matches[1]);
        preg_match(
            '/name="token" value="([A-Za-z0-9]{40})"/',
            $preview->getContent(),
            $tokenMatch
        );
        $this->assertNotEmpty($tokenMatch[1] ?? null);

        $mappedPreview = $this->post(route('products.import.mapping'), [
            'token' => $tokenMatch[1],
            'mappings' => [
                $matches[1][0] => $food->id,
                $matches[1][1] => $drink->id,
            ],
        ]);

        $mappedPreview->assertOk()
            ->assertSee('Menu Kuah')
            ->assertSee('Makanan')
            ->assertSee('Minuman Dingin')
            ->assertSee('Es Teh')
            ->assertSee('Data valid: 2')
            ->assertSee('Data yang akan diimport: 2')
            ->assertSee('Konfirmasi Import');
        $this->assertDatabaseCount('products', 0);
        $this->assertDatabaseCount('categories', 2);

        $this->post(route('products.import.commit'), ['token' => $tokenMatch[1]])
            ->assertRedirect(route('products.import'))
            ->assertSessionHas('import_report.created', 2);

        $this->assertDatabaseCount('products', 2);
        $this->assertDatabaseCount('categories', 2);
        $this->assertDatabaseHas('products', [
            'name' => 'Bakso Aci',
            'category_id' => $food->id,
        ]);
        $this->assertDatabaseHas('products', [
            'name' => 'Es Teh',
            'category_id' => $drink->id,
        ]);
    }

    public function test_unmapped_categories_and_duplicate_products_block_the_import(): void
    {
        $this->createCategory();
        $this->createProduct('Produk Lama');

        $preview = $this->actingAs(User::factory()->create())
            ->post(route('products.import.preview'), [
                'file' => $this->makeWorkbook([
                    ['Produk', 'Kategori'],
                    ['Produk Lama', 'Menu'],
                    ['Produk Baru', 'Kategori Tidak Ada'],
                ]),
            ]);

        $preview->assertOk()
            ->assertSee('Data duplikat: 1')
            ->assertSee('Data bermasalah: 1')
            ->assertSee('Kategori Excel belum dipetakan ke kategori database.')
            ->assertDontSee('Konfirmasi Import');

        preg_match(
            '/name="mappings\[([a-f0-9]{64})\]"/',
            $preview->getContent(),
            $mappingMatch
        );
        preg_match(
            '/name="token" value="([A-Za-z0-9]{40})"/',
            $preview->getContent(),
            $tokenMatch
        );
        $this->assertNotEmpty($mappingMatch[1] ?? null);
        $this->assertNotEmpty($tokenMatch[1] ?? null);

        $this->post(route('products.import.mapping'), [
            'token' => $tokenMatch[1],
            'mappings' => [$mappingMatch[1] => $this->createCategory()->id],
        ])
            ->assertOk()
            ->assertSee('Data duplikat: 1')
            ->assertSee('Data valid: 1')
            ->assertDontSee('Konfirmasi Import');

        $this->assertDatabaseCount('products', 1);
    }

    public function test_mapping_rejects_categories_outside_the_preview_choices(): void
    {
        $this->createCategory();
        $preview = $this->actingAs(User::factory()->create())
            ->post(route('products.import.preview'), [
                'file' => $this->makeWorkbook([
                    ['Produk', 'Kategori'],
                    ['Bakso Aci', 'Menu Baru'],
                ]),
            ]);

        preg_match(
            '/name="mappings\[([a-f0-9]{64})\]"/',
            $preview->getContent(),
            $mappingMatch
        );
        preg_match(
            '/name="token" value="([A-Za-z0-9]{40})"/',
            $preview->getContent(),
            $tokenMatch
        );
        $this->assertNotEmpty($mappingMatch[1] ?? null);
        $this->assertNotEmpty($tokenMatch[1] ?? null);

        $this->post(route('products.import.mapping'), [
            'token' => $tokenMatch[1],
            'mappings' => [$mappingMatch[1] => 999999],
        ])
            ->assertOk()
            ->assertSee('Kategori pilihan sudah tidak tersedia.')
            ->assertDontSee('Konfirmasi Import');

        $this->assertDatabaseCount('products', 0);
        $this->assertDatabaseCount('categories', 1);
    }

    public function test_invalid_or_incomplete_headers_are_rejected(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('products.import.preview'), [
                'file' => $this->makeWorkbook([
                    ['Produk', 'Kategori', 'Tambahan'],
                    ['Bakso Aci', 'Menu', 'X'],
                ]),
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('products', 0);
    }

    public function test_product_import_page_and_template_are_available(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('products.index'))
            ->assertOk()
            ->assertSee('Import Produk')
            ->assertSee(route('products.import'));

        $this->get(route('products.import'))
            ->assertOk()
            ->assertDontSee('Harga')
            ->assertDontSee('selling_price');

        $this->get(route('products.import.template'))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    private function createCategory(): Category
    {
        return Category::firstOrCreate(['name' => 'Menu']);
    }

    private function createProduct(string $name): Product
    {
        return Product::create([
            'category_id' => $this->createCategory()->id,
            'code' => 'PR-'.fake()->unique()->numerify('######'),
            'name' => $name,
            'selling_price' => 1000,
        ]);
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

    private function makeWorkbook(array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray($rows, null, 'A1');
        $path = tempnam(sys_get_temp_dir(), 'product-master-');

        try {
            IOFactory::createWriter($spreadsheet, 'Xlsx')->save($path);
            $content = file_get_contents($path);
        } finally {
            unlink($path);
            $spreadsheet->disconnectWorksheets();
        }

        return UploadedFile::fake()->createWithContent('products.xlsx', $content);
    }
}

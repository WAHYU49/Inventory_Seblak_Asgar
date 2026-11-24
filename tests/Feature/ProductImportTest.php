<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

class ProductImportTest extends TestCase
{
    use DatabaseTransactions;

    public function test_products_reuse_normalized_categories_without_touching_ingredients_or_stock(): void
    {
        $category = Category::create(['name' => 'Makanan Pedas']);
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
                ['Kategori', 'Produk'],
                ['  MAKANAN    PEDAS  ', 'Bakso Aci'],
                ['Makanan Pedas', 'Seblak Original'],
            ]),
        ]);

        $preview->assertOk()
            ->assertSee('Data yang akan diimport: 2')
            ->assertSee('Bakso Aci')
            ->assertSee('Kategori tersedia')
            ->assertDontSee('Harga');
        $this->assertDatabaseCount('products', 0);
        $this->assertDatabaseCount('categories', 1);
        $this->assertSame(3.0, (float) $ingredient->fresh()->current_stock);

        $this->post(route('products.import.commit'), [
            'token' => $this->tokenFrom($preview),
        ])
            ->assertRedirect(route('products.import'))
            ->assertSessionHas('import_report.created', 2);

        $products = Product::orderBy('id')->get();
        $this->assertSame(['Bakso Aci', 'Seblak Original'], $products->pluck('name')->all());
        $this->assertSame([$category->id, $category->id], $products->pluck('category_id')->all());
        $this->assertSame(2, $products->pluck('code')->unique()->count());
        $this->assertTrue($products->every(fn (Product $product) => str_starts_with($product->code, 'PRD-')));
        $this->assertSame('0.00', $products->first()->selling_price);
        $this->assertSame('Bakso Aci', $ingredient->fresh()->name);
        $this->assertSame(3.0, (float) $ingredient->fresh()->current_stock);
        $this->assertDatabaseCount('ingredients', 1);
    }

    public function test_new_categories_are_created_once_only_after_confirmation_and_appear_on_category_page(): void
    {
        $user = User::factory()->create();
        $preview = $this->actingAs($user)->post(route('products.import.preview'), [
            'file' => $this->makeWorkbook([
                ['Produk', 'Kategori'],
                ['Bakso Aci', 'Menu Kuah'],
                ['Seblak Kuah', 'MENU   KUAH'],
                ['Es Teh', 'Minuman'],
            ]),
        ]);

        $preview->assertOk()
            ->assertSee('Kategori baru')
            ->assertSee('Produk valid yang akan diimport: 3')
            ->assertSee('Data yang akan diimport: 3')
            ->assertSee('Konfirmasi Import');
        $this->assertDatabaseCount('products', 0);
        $this->assertDatabaseCount('categories', 0);

        $this->post(route('products.import.commit'), [
            'token' => $this->tokenFrom($preview),
        ])
            ->assertRedirect(route('products.import'))
            ->assertSessionHas('import_report.created', 3);

        $this->assertDatabaseCount('categories', 2);
        $this->assertDatabaseHas('categories', ['name' => 'Menu Kuah']);
        $this->assertDatabaseHas('categories', ['name' => 'Minuman']);
        $this->assertDatabaseHas('products', [
            'name' => 'Bakso Aci',
            'category_id' => Category::where('name', 'Menu Kuah')->value('id'),
        ]);
        $this->assertDatabaseHas('products', [
            'name' => 'Seblak Kuah',
            'category_id' => Category::where('name', 'Menu Kuah')->value('id'),
        ]);
        $this->assertDatabaseHas('products', [
            'name' => 'Es Teh',
            'category_id' => Category::where('name', 'Minuman')->value('id'),
        ]);

        $this->get(route('categories.index'))
            ->assertOk()
            ->assertSee('Menu Kuah')
            ->assertSee('Minuman');
    }

    public function test_existing_and_in_file_duplicates_are_skipped_while_valid_products_remain_importable(): void
    {
        $category = Category::create(['name' => 'Menu']);
        $existing = $this->createProduct('Produk Lama', $category);

        $preview = $this->actingAs(User::factory()->create())
            ->post(route('products.import.preview'), [
                'file' => $this->makeWorkbook([
                    ['Produk', 'Kategori'],
                    [' produk lama ', 'Menu'],
                    ['Produk Baru', 'Kategori Baru'],
                    ['  produk   baru ', 'Kategori Baru'],
                ]),
            ]);

        $preview->assertOk()
            ->assertSee('Produk valid yang akan diimport: 1')
            ->assertSee('Produk duplikat dilewati: 2')
            ->assertSee('Data bermasalah: 0')
            ->assertSee('Data yang akan diimport: 1')
            ->assertSee('Nama produk sudah ada di database.')
            ->assertSee('Nama produk duplikat di dalam file')
            ->assertSee('Konfirmasi Import');
        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseCount('categories', 1);
        $this->assertDatabaseHas('products', ['id' => $existing->id, 'name' => 'Produk Lama']);

        $this->post(route('products.import.commit'), [
            'token' => $this->tokenFrom($preview),
        ])
            ->assertRedirect(route('products.import'))
            ->assertSessionHas('import_report.created', 1)
            ->assertSessionHas('import_report.duplicates', 2);

        $this->assertDatabaseCount('products', 2);
        $this->assertDatabaseHas('products', [
            'id' => $existing->id,
            'name' => 'Produk Lama',
            'selling_price' => 1000,
        ]);
        $this->assertDatabaseHas('products', ['name' => 'Produk Baru']);
        $this->assertDatabaseCount('categories', 2);
    }

    public function test_preview_and_confirmation_report_the_expected_counts_for_120_rows(): void
    {
        $category = Category::create(['name' => 'Menu']);
        $existing = $this->createProduct('Nasgor Jawa', $category);
        $rows = [['Produk', 'Kategori']];

        foreach (range(1, 120) as $rowNumber) {
            $rows[] = [
                $rowNumber === 83 ? 'Nasgor Jawa' : 'Produk '.$rowNumber,
                'Menu',
            ];
        }

        $preview = $this->actingAs(User::factory()->create())
            ->post(route('products.import.preview'), [
                'file' => $this->makeWorkbook($rows),
            ]);

        $preview->assertOk()
            ->assertSee('Total data: 120')
            ->assertSee('Produk valid yang akan diimport: 119')
            ->assertSee('Produk duplikat dilewati: 1')
            ->assertSee('Data bermasalah: 0')
            ->assertSee('Data yang akan diimport: 119')
            ->assertSee('Konfirmasi Import');

        $this->post(route('products.import.commit'), [
            'token' => $this->tokenFrom($preview),
        ])
            ->assertRedirect(route('products.import'))
            ->assertSessionHas('import_report.created', 119)
            ->assertSessionHas('import_report.duplicates', 1);

        $this->assertDatabaseCount('products', 120);
        $this->assertSame(1, Product::where('name', 'Nasgor Jawa')->count());
        $this->assertSame($existing->code, $existing->fresh()->code);
        $this->assertSame(
            [$category->id],
            Product::query()->distinct()->pluck('category_id')->all()
        );
    }

    public function test_product_added_after_preview_is_skipped_and_other_products_are_imported(): void
    {
        $existingCategory = Category::create(['name' => 'Kategori Lama']);
        $preview = $this->actingAs(User::factory()->create())
            ->post(route('products.import.preview'), [
                'file' => $this->makeWorkbook([
                    ['Produk', 'Kategori'],
                    ['Produk Terbaru', 'Kategori yang tak dipakai'],
                    ['Produk Aman', 'Kategori Aman'],
                ]),
            ]);

        $preview->assertOk()
            ->assertSee('Produk valid yang akan diimport: 2')
            ->assertSee('Produk duplikat dilewati: 0')
            ->assertSee('Data yang akan diimport: 2');

        $existing = $this->createProduct('Produk Terbaru', $existingCategory);

        $this->post(route('products.import.commit'), [
            'token' => $this->tokenFrom($preview),
        ])
            ->assertRedirect(route('products.import'))
            ->assertSessionHas('import_report.created', 1)
            ->assertSessionHas('import_report.duplicates', 1);

        $this->assertDatabaseCount('products', 2);
        $this->assertDatabaseHas('products', ['id' => $existing->id, 'name' => 'Produk Terbaru']);
        $this->assertDatabaseHas('products', ['name' => 'Produk Aman']);
        $this->assertDatabaseHas('categories', ['name' => 'Kategori Aman']);
        $this->assertDatabaseMissing('categories', ['name' => 'Kategori yang tak dipakai']);
        $this->assertDatabaseCount('categories', 2);
    }

    public function test_problematic_rows_still_block_import_when_duplicates_can_be_skipped(): void
    {
        $category = Category::create(['name' => 'Menu']);
        $this->createProduct('Produk Lama', $category);

        $preview = $this->actingAs(User::factory()->create())
            ->post(route('products.import.preview'), [
                'file' => $this->makeWorkbook([
                    ['Produk', 'Kategori'],
                    ['Produk Lama', 'Menu'],
                    ['Produk Baru', 'Kategori Baru'],
                    ['Produk Tanpa Kategori', ''],
                ]),
            ]);

        $preview->assertOk()
            ->assertSee('Produk valid yang akan diimport: 1')
            ->assertSee('Produk duplikat dilewati: 1')
            ->assertSee('Data bermasalah: 1')
            ->assertSee('Data yang akan diimport: 0')
            ->assertDontSee('Konfirmasi Import');
        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseCount('categories', 1);
    }

    public function test_same_product_name_with_different_categories_is_marked_as_ambiguous(): void
    {
        $preview = $this->actingAs(User::factory()->create())
            ->post(route('products.import.preview'), [
                'file' => $this->makeWorkbook([
                    ['Produk', 'Kategori'],
                    ['Bakso Aci', 'Makanan'],
                    [' bakso   aci ', 'Minuman'],
                ]),
            ]);

        $preview->assertOk()
            ->assertSee('memiliki kategori berbeda dalam file')
            ->assertSee('Data bermasalah: 2')
            ->assertSee('Data yang akan diimport: 0')
            ->assertDontSee('Konfirmasi Import');
        $this->assertDatabaseCount('products', 0);
        $this->assertDatabaseCount('categories', 0);
    }

    public function test_empty_categories_are_reported_and_never_created(): void
    {
        $preview = $this->actingAs(User::factory()->create())
            ->post(route('products.import.preview'), [
                'file' => $this->makeWorkbook([
                    ['Produk', 'Kategori'],
                    ['Bakso Aci', '   '],
                ]),
            ]);

        $preview->assertOk()
            ->assertSee('Kategori wajib diisi.')
            ->assertSee('Tidak ada kategori')
            ->assertSee('Data bermasalah: 1')
            ->assertDontSee('Konfirmasi Import');
        $this->assertDatabaseCount('products', 0);
        $this->assertDatabaseCount('categories', 0);
    }

    public function test_import_rolls_back_created_categories_when_product_persistence_fails(): void
    {
        $preview = $this->actingAs(User::factory()->create())
            ->post(route('products.import.preview'), [
                'file' => $this->makeWorkbook([
                    ['Produk', 'Kategori'],
                    ['Bakso Aci', 'Kategori Baru'],
                ]),
            ]);

        Event::listen('eloquent.creating: '.Product::class, function () {
            throw new QueryException(
                'mysql_testing',
                'insert into products',
                [],
                new \RuntimeException('Simulated product insert failure.')
            );
        });

        $this->post(route('products.import.commit'), [
            'token' => $this->tokenFrom($preview),
        ])
            ->assertRedirect(route('products.import'))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('products', 0);
        $this->assertDatabaseCount('categories', 0);
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
        $this->assertDatabaseCount('categories', 0);
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
            ->assertDontSee('selling_price')
            ->assertSee('Kategori dari Excel akan digunakan');

        $this->get(route('products.import.template'))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    private function createProduct(string $name, Category $category): Product
    {
        return Product::create([
            'category_id' => $category->id,
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

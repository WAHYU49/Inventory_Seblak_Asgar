<?php

namespace App\Http\Controllers;

use App\Exports\ProductImportTemplateExport;
use App\Imports\ProductMasterImport;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Exceptions\NoTypeDetectedException;
use PhpOffice\PhpSpreadsheet\Reader\Exception as SpreadsheetReaderException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProductImportController extends Controller
{
    public function index()
    {
        return view('products.import');
    }

    public function template(): BinaryFileResponse
    {
        return Excel::download(
            new ProductImportTemplateExport(),
            'template_import_produk.xlsx',
            \Maatwebsite\Excel\Excel::XLSX
        );
    }

    public function preview(Request $request)
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:5120'],
        ]);

        $import = new ProductMasterImport();

        try {
            Excel::import($import, $validated['file']);
        } catch (NoTypeDetectedException | SpreadsheetReaderException) {
            return back()->withErrors([
                'file' => 'File Excel tidak dapat dibaca. Periksa kembali format dan isi file.',
            ]);
        }

        $rows = $import->rows->values();

        if ($rows->isEmpty()) {
            return back()->withErrors([
                'file' => 'File Excel tidak memiliki baris header.',
            ]);
        }

        $headers = array_map(
            fn ($header) => $this->normalizeName((string) $header),
            array_values($rows->first()->all())
        );
        if (
            count($headers) !== 2
            || count(array_unique($headers)) !== 2
            || ! in_array('produk', $headers, true)
            || ! in_array('kategori', $headers, true)
        ) {
            return back()->withErrors([
                'file' => 'Header Excel harus berisi tepat dua kolom: Produk dan Kategori.',
            ]);
        }

        $headerIndexes = [];

        foreach ($headers as $index => $header) {
            if ($header === 'produk' || $header === 'kategori') {
                $headerIndexes[$header] = $index;
            }
        }

        $dataRows = $rows->slice(1)->values();

        if ($dataRows->count() > 5000) {
            return back()->withErrors([
                'file' => 'Maksimal 5.000 baris data dapat diproses dalam satu import.',
            ]);
        }

        $existingNames = Product::query()
            ->get(['name'])
            ->groupBy(fn (Product $product) => $this->normalizeName($product->name));
        $categories = Category::query()
            ->orderBy('name')
            ->get(['id', 'name']);
        $categoryOptions = $categories
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
            ])
            ->all();
        $categoriesByName = $categories
            ->groupBy(fn (Category $category) => $this->normalizeName($category->name));
        $seenNames = [];
        $previewRows = [];
        $mappingCatalog = [];
        $skipped = 0;

        foreach ($dataRows as $index => $row) {
            $values = array_values($row->all());

            if ($this->isEmptyRow($values)) {
                $skipped++;
                continue;
            }

            $rowNumber = $index + 2;
            $name = trim((string) ($values[$headerIndexes['produk']] ?? ''));
            $categoryName = trim((string) ($values[$headerIndexes['kategori']] ?? ''));
            $isDuplicate = false;
            $baseErrors = [];
            $normalizedName = $this->normalizeName($name);

            if ($name === '') {
                $baseErrors[] = 'Nama produk wajib diisi.';
            } elseif (mb_strlen($name) > 255) {
                $baseErrors[] = 'Nama produk maksimal 255 karakter.';
            } elseif ($existingNames->has($normalizedName)) {
                $baseErrors[] = 'Nama produk sudah ada di database.';
                $isDuplicate = true;
            } elseif (isset($seenNames[$normalizedName])) {
                $baseErrors[] = 'Nama produk duplikat di dalam file (baris '
                    .$seenNames[$normalizedName].').';
                $isDuplicate = true;
            }

            if ($normalizedName !== '' && ! isset($seenNames[$normalizedName])) {
                $seenNames[$normalizedName] = $rowNumber;
            }

            $categoryId = null;
            $mappedCategory = null;
            $categoryKey = null;

            if ($categoryName === '') {
                $categoryRequired = true;
            } else {
                $categoryRequired = false;
                $matchingCategories = $categoriesByName->get(
                    $this->normalizeName($categoryName),
                    collect()
                );

                if ($matchingCategories->count() === 1) {
                    $category = $matchingCategories->first();
                    $categoryId = $category->id;
                    $mappedCategory = $category->name;
                } else {
                    $categoryKey = hash(
                        'sha256',
                        $this->normalizeName($categoryName)
                    );

                    if (! isset($mappingCatalog[$categoryKey])) {
                        $mappingCatalog[$categoryKey] = [
                            'source_name' => $categoryName,
                            'options' => $categoryOptions,
                        ];
                    }
                }
            }

            $previewRows[] = [
                'row_number' => $rowNumber,
                'name' => $name,
                'category_name' => $categoryName,
                'mapped_category' => $mappedCategory,
                'category_id' => $categoryId,
                'category_key' => $categoryKey,
                'category_required' => $categoryRequired,
                'base_errors' => $baseErrors,
                'is_duplicate' => $isDuplicate,
            ];
        }

        if ($previewRows === []) {
            return back()->withErrors([
                'file' => 'File tidak memiliki baris data produk.',
            ]);
        }

        $token = Str::random(40);
        $preview = [
            'user_id' => $request->user()->id,
            'rows' => $previewRows,
            'mapping_catalog' => $mappingCatalog,
            'mappings' => [],
            'category_options' => $categoryOptions,
            'skipped' => $skipped,
            'completed' => false,
        ];
        $this->refreshPreviewState($preview);
        $request->session()->put('product_imports.'.$token, $preview);

        return $this->previewView($token, $preview);
    }

    public function applyMappings(Request $request)
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'size:40'],
        ]);
        $sessionKey = 'product_imports.'.$validated['token'];
        $preview = $request->session()->get($sessionKey);

        abort_unless(
            is_array($preview)
                && ($preview['user_id'] ?? null) === $request->user()->id,
            404
        );

        $validator = Validator::make($request->all(), [
            'mappings' => ['required', 'array'],
            'mappings.*' => ['required', 'integer'],
        ]);

        if ($validator->fails()) {
            return $this->previewView(
                $validated['token'],
                $preview,
                ['Pilih kategori database untuk setiap kategori Excel yang belum cocok.']
            );
        }

        $mappings = $validator->validated()['mappings'];
        $expectedKeys = array_keys($preview['mapping_catalog']);

        if (
            array_diff($expectedKeys, array_keys($mappings)) !== []
            || array_diff(array_keys($mappings), $expectedKeys) !== []
        ) {
            return $this->previewView(
                $validated['token'],
                $preview,
                ['Pemetaan kategori tidak sesuai dengan kategori yang ditampilkan.']
            );
        }

        $categoryIds = array_map('intval', array_values($mappings));
        $categories = Category::query()
            ->whereIn('id', $categoryIds)
            ->get(['id', 'name'])
            ->keyBy('id');

        foreach ($mappings as $key => $categoryId) {
            $categoryId = (int) $categoryId;
            $category = $categories->get($categoryId);
            $availableIds = array_column(
                $preview['mapping_catalog'][$key]['options'],
                'id'
            );

            if (! $category || ! in_array($categoryId, $availableIds, true)) {
                return $this->previewView(
                    $validated['token'],
                    $preview,
                    ['Kategori pilihan sudah tidak tersedia. Muat ulang preview sebelum melanjutkan.']
                );
            }

            $preview['mappings'][$key] = [
                'id' => $category->id,
                'name' => $category->name,
            ];
        }

        $this->refreshPreviewState($preview);
        $request->session()->put($sessionKey, $preview);

        return $this->previewView($validated['token'], $preview);
    }

    public function commit(Request $request)
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'size:40'],
        ]);

        $sessionKey = 'product_imports.'.$validated['token'];
        $preview = $request->session()->get($sessionKey);

        abort_unless(
            is_array($preview)
                && ($preview['user_id'] ?? null) === $request->user()->id,
            404
        );

        if ($preview['completed'] ?? false) {
            return redirect()
                ->route('products.import')
                ->with('success', 'Import ini sudah diproses; tidak ada produk tambahan.');
        }

        if ($preview['errors'] !== [] || $preview['valid_rows'] === []) {
            return redirect()->route('products.import');
        }

        $created = 0;

        try {
            DB::transaction(function () use ($preview, &$created) {
                $existingNames = Product::query()
                    ->lockForUpdate()
                    ->get(['name'])
                    ->map(fn (Product $product) => $this->normalizeName($product->name))
                    ->all();

                foreach ($preview['valid_rows'] as $row) {
                    if (in_array($this->normalizeName($row['name']), $existingNames, true)) {
                        throw ValidationException::withMessages([
                            'file' => 'Nama produk '.$row['name']
                                .' sudah ditambahkan sejak preview. Unggah ulang file untuk memperbarui preview.',
                        ]);
                    }

                    $category = Category::query()
                        ->whereKey($row['category_id'])
                        ->lockForUpdate()
                        ->first();

                    if (
                        ! $category
                        || $this->normalizeName($category->name)
                            !== $this->normalizeName($row['mapped_category_name'])
                    ) {
                        throw ValidationException::withMessages([
                            'file' => 'Kategori untuk produk '.$row['name']
                                .' sudah tidak tersedia. Unggah ulang file untuk memperbarui preview.',
                        ]);
                    }

                    $code = $row['code'];

                    while (Product::query()->where('code', $code)->exists()) {
                        $code = $this->generateProductCode();
                    }

                    Product::create([
                        'category_id' => $row['category_id'],
                        'code' => $code,
                        'name' => $row['name'],
                    ]);

                    $existingNames[] = $this->normalizeName($row['name']);
                    $created++;
                }
            });
        } catch (ValidationException $exception) {
            return redirect()
                ->route('products.import')
                ->with('error', $exception->errors()['file'][0]);
        } catch (QueryException $exception) {
            report($exception);

            return redirect()
                ->route('products.import')
                ->with(
                    'error',
                    'Import gagal disimpan. Tidak ada perubahan yang diterapkan; periksa data dan coba kembali.'
                );
        }

        $preview['completed'] = true;
        $request->session()->put($sessionKey, $preview);

        return redirect()
            ->route('products.import')
            ->with('import_report', ['created' => $created]);
    }

    private function generateProductCode(array $reservedCodes = []): string
    {
        do {
            $code = 'PRD-'.Str::upper(Str::random(12));
        } while (
            in_array($code, $reservedCodes, true)
            || Product::query()->where('code', $code)->exists()
        );

        return $code;
    }

    private function refreshPreviewState(array &$preview): void
    {
        $validRows = [];
        $errors = [];
        $reservedCodes = [];
        $duplicates = 0;
        $problematic = 0;

        foreach ($preview['rows'] as &$row) {
            $rowErrors = $row['base_errors'];
            $categoryId = $row['category_id'];
            $mappedCategory = $row['mapped_category'];

            if ($row['category_required']) {
                $rowErrors[] = 'Kategori wajib diisi.';
                $categoryId = null;
                $mappedCategory = null;
            } elseif ($row['category_key'] !== null) {
                $mapping = $preview['mappings'][$row['category_key']] ?? null;

                if ($mapping === null) {
                    $rowErrors[] = 'Kategori Excel belum dipetakan ke kategori database.';
                    $categoryId = null;
                    $mappedCategory = null;
                } else {
                    $categoryId = $mapping['id'];
                    $mappedCategory = $mapping['name'];
                }
            }

            $isDuplicate = $row['is_duplicate'];

            if ($isDuplicate) {
                $duplicates++;
            } elseif ($rowErrors !== []) {
                $problematic++;
            }

            $row['category_id'] = $categoryId;
            $row['mapped_category'] = $mappedCategory;
            $row['errors'] = $rowErrors;
            $row['status'] = $isDuplicate
                ? 'Duplikat'
                : ($rowErrors === [] ? 'Valid' : 'Bermasalah');

            if ($rowErrors !== []) {
                $errors[] = [
                    'row' => $row['row_number'],
                    'message' => implode(' ', $rowErrors),
                ];
                continue;
            }

            $code = $row['code'] ?? $this->generateProductCode($reservedCodes);
            $row['code'] = $code;
            $reservedCodes[] = $code;
            $validRows[] = [
                'row_number' => $row['row_number'],
                'name' => $row['name'],
                'category_id' => $categoryId,
                'mapped_category_name' => $mappedCategory,
                'code' => $code,
            ];
        }
        unset($row);

        $preview['valid_rows'] = $validRows;
        $preview['errors'] = $errors;
        $preview['counts'] = [
            'total' => count($preview['rows']),
            'valid' => count($validRows),
            'duplicates' => $duplicates,
            'problematic' => $problematic,
            'to_import' => $errors === [] ? count($validRows) : 0,
            'skipped' => $preview['skipped'],
        ];
    }

    private function previewView(
        string $token,
        array $preview,
        array $mappingErrors = []
    ) {
        return view('products.import-preview', [
            'token' => $token,
            'rows' => $preview['rows'],
            'errors' => $preview['errors'],
            'mappingErrors' => $mappingErrors,
            'mappingCatalog' => $preview['mapping_catalog'],
            'mappings' => $preview['mappings'],
            'counts' => $preview['counts'],
            'completed' => $preview['completed'],
        ]);
    }

    private function normalizeName(string $value): string
    {
        $collapsed = preg_replace('/\s+/u', ' ', trim($value));

        return mb_strtolower($collapsed ?? trim($value));
    }

    private function isEmptyRow(array $values): bool
    {
        foreach ($values as $value) {
            if ($value !== null && trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }
}

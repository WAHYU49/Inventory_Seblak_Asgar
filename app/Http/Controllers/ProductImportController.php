<?php

namespace App\Http\Controllers;

use App\Exports\ProductImportTemplateExport;
use App\Imports\ProductMasterImport;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Product;
use App\Models\Recipe;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Exceptions\NoTypeDetectedException;
use Maatwebsite\Excel\Facades\Excel;
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
            new ProductImportTemplateExport,
            'template_import_produk.xlsx',
            \Maatwebsite\Excel\Excel::XLSX
        );
    }

    public function preview(Request $request)
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:5120'],
        ]);

        $import = new ProductMasterImport;

        try {
            Excel::import($import, $validated['file']);
        } catch (NoTypeDetectedException|SpreadsheetReaderException) {
            return back()->withErrors([
                'file' => 'File Excel tidak dapat dibaca. Periksa kembali format dan isi file.',
            ]);
        }

        $rows = $import->productRows()->values();

        if ($rows->isEmpty()) {
            return back()->withErrors([
                'file' => 'Workbook harus memiliki sheet PRODUK dengan baris header.',
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
            $headerIndexes[$header] = $index;
        }

        $recipeRows = $import->recipeRows()->values();
        $recipeHeaders = $recipeRows->isEmpty()
            ? []
            : array_map(
                fn ($header) => $this->normalizeName((string) $header),
                array_values($recipeRows->first()->all())
            );
        if (
            count($recipeHeaders) !== 4
            || count(array_unique($recipeHeaders)) !== 4
            || ! in_array('produk', $recipeHeaders, true)
            || ! in_array('bahan baku', $recipeHeaders, true)
            || ! in_array('jumlah', $recipeHeaders, true)
            || ! in_array('satuan', $recipeHeaders, true)
        ) {
            return back()->withErrors([
                'file' => 'Sheet RESEP MENU harus berisi header Produk, Bahan Baku, Jumlah, dan Satuan.',
            ]);
        }

        $dataRows = $rows->slice(1)->values();
        $recipeDataRows = $recipeRows->slice(1)->values();

        if ($dataRows->count() > 5000) {
            return back()->withErrors([
                'file' => 'Maksimal 5.000 baris data dapat diproses dalam satu import.',
            ]);
        }
        if ($recipeDataRows->count() > 10000) {
            return back()->withErrors([
                'file' => 'Maksimal 10.000 baris detail resep dapat diproses dalam satu import.',
            ]);
        }

        $existingProducts = Product::query()->with('recipe')->get(['id', 'name']);
        $existingProductsByName = [];
        foreach ($existingProducts as $product) {
            $existingProductsByName[$this->normalizeName($product->name)] ??= $product;
        }
        $existingNames = array_keys($existingProductsByName);
        $existingCategories = Category::query()
            ->orderBy('id')
            ->get(['id', 'name']);
        $categoryIdsByName = [];

        foreach ($existingCategories as $category) {
            $normalizedCategoryName = $this->normalizeName($category->name);

            if ($normalizedCategoryName !== '') {
                $categoryIdsByName[$normalizedCategoryName] ??= $category->id;
            }
        }

        $seenNames = [];
        $nameOccurrences = [];
        $previewRows = [];
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
            $normalizedName = $this->normalizeName($name);
            $normalizedCategoryName = $this->normalizeName($categoryName);
            $rowErrors = [];
            $isDuplicate = false;
            $duplicateMessage = null;

            if ($name === '') {
                $rowErrors[] = 'Nama produk wajib diisi.';
            } elseif (mb_strlen($name) > 255) {
                $rowErrors[] = 'Nama produk maksimal 255 karakter.';
            } elseif (in_array($normalizedName, $existingNames, true)) {
                $duplicateMessage = 'Nama produk sudah ada di database.';
                $isDuplicate = true;
            } elseif (isset($seenNames[$normalizedName])) {
                $duplicateMessage = 'Nama produk duplikat di dalam file (baris '
                    .$seenNames[$normalizedName].').';
                $isDuplicate = true;
            }

            if ($normalizedName !== '') {
                $seenNames[$normalizedName] ??= $rowNumber;
                $nameOccurrences[$normalizedName][] = count($previewRows);
            }

            if ($categoryName === '') {
                $rowErrors[] = 'Kategori wajib diisi.';
            } elseif (mb_strlen($categoryName) > 255) {
                $rowErrors[] = 'Nama kategori maksimal 255 karakter.';
            }

            $previewRows[] = [
                'row_number' => $rowNumber,
                'name' => $name,
                'category_name' => $categoryName,
                'category_status' => $normalizedCategoryName === ''
                    ? null
                    : (isset($categoryIdsByName[$normalizedCategoryName])
                        ? 'Kategori tersedia'
                        : 'Kategori baru'),
                'normalized_category_name' => $normalizedCategoryName,
                'base_errors' => $rowErrors,
                'duplicate_message' => $duplicateMessage,
                'is_duplicate' => $isDuplicate,
                'is_ambiguous' => false,
            ];
        }

        foreach ($nameOccurrences as $occurrences) {
            $categoryNames = [];

            foreach ($occurrences as $rowIndex) {
                $categoryName = $previewRows[$rowIndex]['normalized_category_name'];
                $categoryNames[$categoryName] = true;
            }

            if (count($categoryNames) < 2) {
                continue;
            }

            $rowNumbers = array_map(
                fn (int $rowIndex) => $previewRows[$rowIndex]['row_number'],
                $occurrences
            );
            $message = 'Nama produk yang sama memiliki kategori berbeda dalam file (baris '
                .implode(', ', $rowNumbers).').';

            foreach ($occurrences as $rowIndex) {
                $previewRows[$rowIndex]['base_errors'][] = $message;
                $previewRows[$rowIndex]['is_ambiguous'] = true;
            }
        }

        $categorySummary = [];

        foreach ($previewRows as $row) {
            $normalizedCategoryName = $row['normalized_category_name'];

            if (
                $normalizedCategoryName !== ''
                && ! isset($categorySummary[$normalizedCategoryName])
            ) {
                $categorySummary[$normalizedCategoryName] = [
                    'name' => $row['category_name'],
                    'status' => $row['category_status'],
                ];
            }
        }

        if ($previewRows === []) {
            return back()->withErrors([
                'file' => 'File tidak memiliki baris data produk.',
            ]);
        }

        $eligibleProductNames = [];
        foreach ($previewRows as $row) {
            if ($row['base_errors'] === [] && $row['normalized_category_name'] !== '') {
                $eligibleProductNames[$this->normalizeName($row['name'])] = $row['name'];
            }
        }

        $recipeHeaderIndexes = array_flip($recipeHeaders);
        $ingredientMatches = [];
        foreach (Ingredient::query()->get(['id', 'name', 'unit', 'is_active']) as $ingredient) {
            $ingredientMatches[$this->normalizeName($ingredient->name)][] = $ingredient;
        }

        $recipePreviewRows = [];
        $recipeGroups = [];
        $seenRecipeIngredients = [];
        $existingRecipeKeys = [];
        $skippedRecipeRows = 0;
        foreach ($recipeDataRows as $index => $row) {
            $values = array_values($row->all());
            if ($this->isEmptyRow($values)) {
                $skippedRecipeRows++;

                continue;
            }

            $rowNumber = $index + 2;
            $productName = trim((string) ($values[$recipeHeaderIndexes['produk']] ?? ''));
            $ingredientName = trim((string) ($values[$recipeHeaderIndexes['bahan baku']] ?? ''));
            $quantityValue = $values[$recipeHeaderIndexes['jumlah']] ?? null;
            $unit = trim((string) ($values[$recipeHeaderIndexes['satuan']] ?? ''));
            $productKey = $this->normalizeName($productName);
            $ingredientKey = $this->normalizeName($ingredientName);
            $rowErrors = [];
            $productMatched = isset($eligibleProductNames[$productKey]);
            $existingProduct = $existingProductsByName[$productKey] ?? null;
            $recipeAlreadyExists = $productMatched
                && $existingProduct !== null
                && $existingProduct->recipe !== null;

            if ($productName === '') {
                $rowErrors[] = 'Nama produk wajib diisi pada sheet RESEP MENU.';
            } elseif (! $productMatched) {
                $rowErrors[] = 'Produk tidak ditemukan atau tidak sesuai pada sheet PRODUK.';
            }

            if ($productMatched && $recipeAlreadyExists) {
                $skippedRecipeRows++;
                $existingRecipeKeys[$productKey] = true;
                $rowErrors = [];
            } else {
                if ($ingredientName === '') {
                    $rowErrors[] = 'Nama bahan baku wajib diisi.';
                } elseif (! isset($ingredientMatches[$ingredientKey])) {
                    $rowErrors[] = 'Bahan baku tidak ditemukan pada tabel ingredients.';
                } elseif (count($ingredientMatches[$ingredientKey]) > 1) {
                    $rowErrors[] = 'Nama bahan baku cocok dengan lebih dari satu bahan baku.';
                }

                $ingredient = $ingredientMatches[$ingredientKey][0] ?? null;
                if ($ingredient !== null && ! $ingredient->is_active) {
                    $rowErrors[] = 'Bahan baku tidak aktif.';
                }

                $quantity = is_numeric($quantityValue) ? (float) $quantityValue : 0.0;
                if (
                    ! is_numeric($quantityValue)
                    || ! is_finite($quantity)
                    || $quantity <= 0
                    || $quantity > 999999999.999
                    || abs($quantity - round($quantity, 3)) > 0.000000001
                ) {
                    $rowErrors[] = 'Jumlah harus berupa angka lebih dari nol dengan maksimal 3 angka desimal.';
                }

                if ($unit === '') {
                    $rowErrors[] = 'Satuan wajib diisi.';
                } elseif (
                    $ingredient !== null
                    && $this->normalizeName($unit) !== $this->normalizeName($ingredient->unit)
                ) {
                    $rowErrors[] = 'Satuan tidak sesuai dengan satuan operasional bahan baku ('
                        .$ingredient->unit.').';
                }

                if ($productMatched && $ingredient !== null) {
                    if (isset($seenRecipeIngredients[$productKey][$ingredient->id])) {
                        $rowErrors[] = 'Bahan baku yang sama tercantum lebih dari sekali pada resep produk ini.';
                    }
                    $seenRecipeIngredients[$productKey][$ingredient->id] = true;
                }

                if ($productName !== '' && $productMatched) {
                    $recipeGroups[$productKey] ??= [
                        'product_key' => $productKey,
                        'product_name' => $eligibleProductNames[$productKey],
                        'details' => [],
                    ];
                    $recipeGroups[$productKey]['details'][] = [
                        'ingredient_name' => $ingredientName,
                        'ingredient_key' => $ingredientKey,
                        'quantity' => $quantity,
                        'unit' => $unit,
                    ];
                }
            }

            $recipePreviewRows[] = [
                'row_number' => $rowNumber,
                'product_name' => $productName,
                'ingredient_name' => $ingredientName,
                'quantity' => $quantityValue === null ? '' : (string) $quantityValue,
                'unit' => $unit,
                'errors' => $rowErrors,
                'message' => implode(' ', $rowErrors),
                'is_existing_recipe' => $recipeAlreadyExists,
            ];
        }

        $recipeProblemCount = count(array_filter(
            $recipePreviewRows,
            fn (array $row) => $row['errors'] !== []
        ));
        $recipeDetailsCount = array_sum(array_map(
            fn (array $group) => count($group['details']),
            $recipeGroups
        ));

        $token = Str::random(40);
        $preview = [
            'user_id' => $request->user()->id,
            'rows' => $previewRows,
            'recipe_rows' => $recipePreviewRows,
            'recipe_groups' => array_values($recipeGroups),
            'categories' => array_values($categorySummary),
            'skipped' => $skipped,
            'skipped_recipe_rows' => $skippedRecipeRows,
            'recipe_counts' => [
                'to_create' => count($recipeGroups),
                'details_to_create' => $recipeDetailsCount,
                'missing_ingredients' => count(array_filter(
                    $recipePreviewRows,
                    fn (array $row) => str_contains($row['message'], 'tidak ditemukan pada tabel ingredients')
                )),
                'product_errors' => count(array_filter(
                    $recipePreviewRows,
                    fn (array $row) => ! $row['is_existing_recipe']
                        && (
                            $row['product_name'] === ''
                            || str_contains($row['message'], 'Produk tidak ditemukan atau tidak sesuai')
                        )
                )),
                'existing' => count($existingRecipeKeys),
                'quantity_unit_errors' => count(array_filter(
                    $recipePreviewRows,
                    fn (array $row) => str_contains($row['message'], 'Jumlah')
                        || str_contains($row['message'], 'Satuan')
                )),
                'problematic' => $recipeProblemCount,
                'skipped_rows' => $skippedRecipeRows,
            ],
            'completed' => false,
        ];
        $this->refreshPreviewState($preview);
        $preview['counts']['problematic'] += $recipeProblemCount;
        $preview['counts']['to_import'] = $preview['counts']['problematic'] === 0
            ? $preview['counts']['valid']
            : 0;
        $preview['errors'] = array_merge(
            $preview['errors'],
            array_values(array_filter(
                array_map(
                    fn (array $row) => $row['errors'] === []
                        ? null
                        : [
                            'row' => $row['row_number'],
                            'message' => implode(' ', $row['errors']),
                        ],
                    $recipePreviewRows
                )
            ))
        );
        $request->session()->put('product_imports.'.$token, $preview);

        return $this->previewView($token, $preview);
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
                ->with('success', 'Import ini sudah diproses; tidak ada perubahan tambahan.');
        }

        if (
            $preview['counts']['problematic'] > 0
            || (
                $preview['valid_rows'] === []
                && $preview['counts']['duplicates'] === 0
            )
        ) {
            return redirect()->route('products.import');
        }

        $created = 0;
        $createdRecipes = 0;
        $createdRecipeDetails = 0;
        $skippedRecipes = $preview['recipe_counts']['existing'];
        $skippedDuplicates = $preview['counts']['duplicates'];

        try {
            DB::transaction(function () use (
                $preview,
                &$created,
                &$createdRecipes,
                &$createdRecipeDetails,
                &$skippedRecipes,
                &$skippedDuplicates
            ) {
                $categories = Category::query()
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get(['id', 'name']);
                $categoryIdsByName = [];

                foreach ($categories as $category) {
                    $normalizedCategoryName = $this->normalizeName($category->name);

                    if ($normalizedCategoryName !== '') {
                        $categoryIdsByName[$normalizedCategoryName] ??= $category->id;
                    }
                }

                $products = Product::query()
                    ->lockForUpdate()
                    ->get(['id', 'name']);
                $productsByName = [];
                foreach ($products as $product) {
                    $productsByName[$this->normalizeName($product->name)] ??= $product;
                }

                foreach ($preview['valid_rows'] as $row) {
                    $normalizedName = $this->normalizeName($row['name']);

                    if (isset($productsByName[$normalizedName])) {
                        $skippedDuplicates++;

                        continue;
                    }

                    $normalizedCategoryName = $this->normalizeName($row['category_name']);
                    $categoryId = $categoryIdsByName[$normalizedCategoryName] ?? null;

                    if ($categoryId === null) {
                        $category = Category::create([
                            'name' => $row['category_name'],
                        ]);
                        $categoryId = $category->id;
                        $categoryIdsByName[$normalizedCategoryName] = $categoryId;
                    }

                    $code = $row['code'];

                    while (Product::query()->where('code', $code)->exists()) {
                        $code = $this->generateProductCode();
                    }

                    $product = Product::create([
                        'category_id' => $categoryId,
                        'code' => $code,
                        'name' => $row['name'],
                    ]);

                    $productsByName[$normalizedName] = $product;
                    $created++;
                }

                $ingredientsByName = [];
                foreach (
                    Ingredient::query()
                        ->lockForUpdate()
                        ->get(['id', 'name', 'unit', 'is_active']) as $ingredient
                ) {
                    $ingredientsByName[$this->normalizeName($ingredient->name)][] = $ingredient;
                }

                $existingRecipeProductIds = Recipe::query()
                    ->lockForUpdate()
                    ->pluck('product_id')
                    ->all();
                $existingRecipeProductIds = array_fill_keys($existingRecipeProductIds, true);

                foreach ($preview['recipe_groups'] as $group) {
                    $product = $productsByName[$group['product_key']] ?? null;
                    if ($product === null) {
                        throw ValidationException::withMessages([
                            'file' => 'Produk berubah setelah preview. Jalankan preview kembali sebelum mengimpor.',
                        ]);
                    }

                    if (isset($existingRecipeProductIds[$product->id])) {
                        $skippedRecipes++;

                        continue;
                    }

                    $recipe = Recipe::create([
                        'product_id' => $product->id,
                        'name' => $product->name,
                        'description' => null,
                        'is_active' => true,
                    ]);
                    $createdRecipes++;

                    foreach ($group['details'] as $detail) {
                        $matches = $ingredientsByName[$detail['ingredient_key']] ?? [];
                        $ingredient = $matches[0] ?? null;

                        if (
                            count($matches) !== 1
                            || $ingredient === null
                            || ! $ingredient->is_active
                            || $this->normalizeName($ingredient->unit)
                                !== $this->normalizeName($detail['unit'])
                        ) {
                            throw ValidationException::withMessages([
                                'file' => 'Data bahan baku atau satuannya berubah setelah preview. Jalankan preview kembali.',
                            ]);
                        }

                        $recipe->details()->create([
                            'ingredient_id' => $ingredient->id,
                            'quantity' => $detail['quantity'],
                        ]);
                        $createdRecipeDetails++;
                    }
                }
            }, 3);
        } catch (QueryException $exception) {
            report($exception);

            return redirect()
                ->route('products.import')
                ->with(
                    'error',
                    'Import gagal disimpan. Tidak ada perubahan yang diterapkan; periksa data dan coba kembali.'
                );
        } catch (ValidationException $exception) {
            return redirect()
                ->route('products.import')
                ->with('error', $exception->errors()['file'][0]);
        }

        $preview['completed'] = true;
        $request->session()->put($sessionKey, $preview);

        return redirect()
            ->route('products.import')
            ->with('import_report', [
                'created' => $created,
                'duplicates' => $skippedDuplicates,
                'recipes_created' => $createdRecipes,
                'recipe_details_created' => $createdRecipeDetails,
                'recipes_skipped' => $skippedRecipes,
            ]);
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

            if ($row['is_duplicate'] && ! $row['is_ambiguous']) {
                $duplicates++;
            }

            if ($rowErrors !== []) {
                $problematic++;
            }

            $row['errors'] = $rowErrors;
            $row['message'] = implode(
                ' ',
                array_filter(array_merge($rowErrors, [$row['duplicate_message']]))
            );
            $row['status'] = $rowErrors !== []
                ? 'Bermasalah'
                : ($row['is_duplicate'] ? 'Duplikat' : 'Valid');

            if ($rowErrors !== []) {
                $errors[] = [
                    'row' => $row['row_number'],
                    'message' => implode(' ', $rowErrors),
                ];

                continue;
            }

            if ($row['is_duplicate']) {
                continue;
            }

            $code = $row['code'] ?? $this->generateProductCode($reservedCodes);
            $row['code'] = $code;
            $reservedCodes[] = $code;
            $validRows[] = [
                'row_number' => $row['row_number'],
                'name' => $row['name'],
                'category_name' => $row['category_name'],
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
            'to_import' => $problematic === 0 ? count($validRows) : 0,
            'skipped' => $preview['skipped'],
        ];
    }

    private function previewView(string $token, array $preview)
    {
        return view('products.import-preview', [
            'token' => $token,
            'rows' => $preview['rows'],
            'recipe_rows' => $preview['recipe_rows'],
            'recipe_counts' => $preview['recipe_counts'],
            'categories' => $preview['categories'],
            'errors' => $preview['errors'],
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

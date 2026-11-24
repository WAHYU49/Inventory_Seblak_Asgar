<?php

namespace App\Http\Controllers;

use App\Exports\ProductImportTemplateExport;
use App\Imports\ProductMasterImport;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
            $headerIndexes[$header] = $index;
        }

        $dataRows = $rows->slice(1)->values();

        if ($dataRows->count() > 5000) {
            return back()->withErrors([
                'file' => 'Maksimal 5.000 baris data dapat diproses dalam satu import.',
            ]);
        }

        $existingNames = Product::query()
            ->get(['name'])
            ->map(fn (Product $product) => $this->normalizeName($product->name))
            ->all();
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

        $token = Str::random(40);
        $preview = [
            'user_id' => $request->user()->id,
            'rows' => $previewRows,
            'categories' => array_values($categorySummary),
            'skipped' => $skipped,
            'completed' => false,
        ];
        $this->refreshPreviewState($preview);
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
                ->with('success', 'Import ini sudah diproses; tidak ada produk tambahan.');
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
        $skippedDuplicates = $preview['counts']['duplicates'];

        try {
            DB::transaction(function () use ($preview, &$created, &$skippedDuplicates) {
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

                $existingNames = Product::query()
                    ->lockForUpdate()
                    ->get(['name'])
                    ->mapWithKeys(fn (Product $product) => [
                        $this->normalizeName($product->name) => true,
                    ])
                    ->all();

                foreach ($preview['valid_rows'] as $row) {
                    $normalizedName = $this->normalizeName($row['name']);

                    if (isset($existingNames[$normalizedName])) {
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

                    Product::create([
                        'category_id' => $categoryId,
                        'code' => $code,
                        'name' => $row['name'],
                    ]);

                    $existingNames[$normalizedName] = true;
                    $created++;
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
        }

        $preview['completed'] = true;
        $request->session()->put($sessionKey, $preview);

        return redirect()
            ->route('products.import')
            ->with('import_report', [
                'created' => $created,
                'duplicates' => $skippedDuplicates,
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

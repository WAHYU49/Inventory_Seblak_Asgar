<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ProcessesIdempotentTransactions;
use App\Imports\SalesResultImport;
use App\Models\Ingredient;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Support\InventoryNumberFormatter;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Exceptions\NoTypeDetectedException;
use PhpOffice\PhpSpreadsheet\Reader\Exception as SpreadsheetReaderException;

class SaleController extends Controller
{
    use ProcessesIdempotentTransactions;

    private const MAX_IMPORT_QUANTITY = 999999999.999;

    /**
     * Menampilkan daftar transaksi penjualan.
     */
    public function index(Request $request)
    {
        $filters = $request->validate([
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
        ]);

        $sales = Sale::with('details.product')
            ->when(
                $filters['start_date'] ?? null,
                fn ($query, $date) => $query->whereDate('sale_date', '>=', $date)
            )
            ->when(
                $filters['end_date'] ?? null,
                fn ($query, $date) => $query->whereDate('sale_date', '<=', $date)
            )
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('sales.index', [
            'sales' => $sales,
            'startDate' => $filters['start_date'] ?? '',
            'endDate' => $filters['end_date'] ?? '',
        ]);
    }

    /**
     * Menampilkan formulir transaksi penjualan.
     */
    public function create()
    {
        $products = Product::where('is_active', true)
            ->whereHas('recipe', function ($query) {
                $query->where('is_active', true);
            })
            ->with('recipe.details.ingredient')
            ->orderBy('name')
            ->get();

        // Menyiapkan data produk dan resep untuk JavaScript.
        $productData = $products->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'recipe' => $product->recipe->details->map(function ($detail) {
                    return [
                        'name' => $detail->ingredient->name ?? 'Bahan tidak tersedia',
                        'quantity' => (float) $detail->quantity,
                        'unit' => $detail->ingredient->unit ?? '',
                        'stock' => (float) ($detail->ingredient->current_stock ?? 0),
                    ];
                })->values(),
            ];
        })->values();

        return view('sales.create', [
            'products' => $products,
            'productData' => $productData,
            'idempotencyKey' => (string) Str::uuid(),
        ]);
    }

    public function import()
    {
        return view('sales.import');
    }

    public function previewImport(Request $request)
    {
        $validated = $request->validate([
            'sale_date' => ['required', 'date'],
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:5120'],
        ]);

        $import = new SalesResultImport();

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
            fn ($header) => $this->normalizeProductName((string) $header),
            array_values($rows->first()->all())
        );

        if (
            count($headers) !== 2
            || $headers[0] !== 'produk'
            || $headers[1] !== 'terjual'
        ) {
            return back()->withErrors([
                'file' => 'Header Excel harus berisi dua kolom: Produk dan Terjual.',
            ]);
        }

        $dataRows = $rows->slice(1)->values();

        if ($dataRows->count() > 5000) {
            return back()->withErrors([
                'file' => 'Maksimal 5.000 baris data dapat diproses dalam satu import.',
            ]);
        }

        $productsByName = Product::query()
            ->get(['id', 'name', 'is_active'])
            ->groupBy(fn (Product $product) => $this->normalizeProductName($product->name));
        $groupedRows = [];
        $previewRows = [];
        $rowErrors = [];
        $emptyRowsSkipped = 0;

        foreach ($dataRows as $index => $row) {
            $rowNumber = $index + 2;
            $values = array_values($row->all());

            if ($this->isEmptyImportRow($values)) {
                $emptyRowsSkipped++;
                continue;
            }

            $errors = [];
            $productName = trim((string) ($values[0] ?? ''));
            $normalizedProductName = $this->normalizeProductName($productName);
            $rawQuantity = $values[1] ?? null;
            $quantity = null;
            $quantityValid = true;

            if (collect(array_slice($values, 2))
                ->contains(fn ($value) => trim((string) $value) !== '')
            ) {
                $errors[] = 'Baris hanya boleh berisi kolom Produk dan Terjual.';
            }

            if ($rawQuantity === null || (is_string($rawQuantity) && trim($rawQuantity) === '')) {
                $errors[] = 'Terjual wajib diisi dengan angka.';
                $quantityValid = false;
            } elseif (! is_numeric($rawQuantity) || ! is_finite((float) $rawQuantity)) {
                $errors[] = 'Terjual harus berupa angka.';
                $quantityValid = false;
            } else {
                $quantity = (float) $rawQuantity;

                if ($quantity < 0) {
                    $errors[] = 'Terjual tidak boleh negatif.';
                    $quantityValid = false;
                } elseif (
                    $quantity > self::MAX_IMPORT_QUANTITY
                    || abs($quantity - round($quantity, 3)) > 0.00000001
                ) {
                    $errors[] = 'Terjual melebihi batas jumlah yang didukung.';
                    $quantityValid = false;
                    $quantity = null;
                } elseif ($quantity > 0) {
                    $quantity = round($quantity, 3);
                }
            }

            $product = null;
            $zeroSkipped = $quantityValid && $quantity === 0.0;
            $matchStatus = $zeroSkipped ? 'Dilewati (0)' : 'Tidak dicocokkan';

            if (! $zeroSkipped && $productName === '') {
                $errors[] = 'Produk wajib diisi.';
                $matchStatus = 'Nama kosong';
            } elseif (! $zeroSkipped && $quantityValid) {
                $matchStatus = 'Tidak ditemukan';
                $matches = $productsByName->get($normalizedProductName, collect());

                if ($matches->isEmpty()) {
                    $errors[] = 'Produk tidak ditemukan di master produk. Periksa nama pada master produk.';
                } elseif ($matches->count() > 1) {
                    $errors[] = 'Nama produk cocok dengan lebih dari satu produk terdaftar.';
                    $matchStatus = 'Nama master ambigu';
                } else {
                    $product = $matches->first();

                    if (! $product->is_active) {
                        $errors[] = 'Produk tidak aktif dan tidak dapat dijual.';
                        $matchStatus = 'Produk nonaktif';
                    } else {
                        $matchStatus = $product->name;
                    }
                }
            } elseif (! $zeroSkipped) {
                $matchStatus = 'Jumlah tidak valid';
            }

            $previewIndex = count($previewRows);
            $previewRows[] = [
                'row' => $rowNumber,
                'product_excel' => $productName,
                'product_matched' => $product ? $product->name : '—',
                'match_status' => $matchStatus,
                'quantity' => $quantity,
                'quantity_valid' => $quantityValid,
                'errors' => $errors,
                'zero_skipped' => $zeroSkipped,
            ];

            if ($errors === [] && $product && $quantity > 0) {
                $productId = $product->id;

                if (! isset($groupedRows[$productId])) {
                    $groupedRows[$productId] = [
                        'product_id' => $productId,
                        'product_name' => $product->name,
                        'source_rows' => [],
                        'total_quantity' => 0,
                    ];
                }

                $groupedRows[$productId]['source_rows'][] = [
                    'row' => $rowNumber,
                    'quantity' => $quantity,
                    'preview_index' => $previewIndex,
                ];
                $groupedRows[$productId]['total_quantity'] = round(
                    $groupedRows[$productId]['total_quantity'] + $quantity,
                    3
                );
            }
        }

        foreach ($groupedRows as $group) {
            if ($group['total_quantity'] > self::MAX_IMPORT_QUANTITY) {
                foreach ($group['source_rows'] as $sourceRow) {
                    $previewRows[$sourceRow['preview_index']]['errors'][] =
                        'Total Terjual untuk produk ini melebihi batas jumlah yang didukung.';
                }

                unset($groupedRows[$group['product_id']]);
            }
        }

        if ($previewRows === []) {
            return back()->withErrors([
                'file' => 'File tidak memiliki baris data penjualan.',
            ]);
        }

        foreach ($previewRows as $row) {
            if ($row['errors'] !== []) {
                $rowErrors[] = [
                    'row' => $row['row'],
                    'message' => implode(' ', $row['errors']),
                ];
            }
        }

        $validRows = array_filter(
            $previewRows,
            fn (array $row) => $row['errors'] === [] && ! $row['zero_skipped']
        );
        $token = Str::random(40);
        $request->session()->put('sales_imports.'.$token, [
            'user_id' => $request->user()->id,
            'sale_date' => $validated['sale_date'],
            'idempotency_key' => (string) Str::uuid(),
            'rows' => array_values($groupedRows),
            'preview_rows' => $previewRows,
            'errors' => $rowErrors,
            'empty_rows_skipped' => $emptyRowsSkipped,
            'summary' => [
                'total_rows' => count($previewRows),
                'valid_rows' => count($validRows),
                'zero_skipped' => count(array_filter(
                    $previewRows,
                    fn (array $row) => $row['zero_skipped']
                )),
                'not_found' => count(array_filter(
                    $previewRows,
                    fn (array $row) => $row['match_status'] === 'Tidak ditemukan'
                )),
                'invalid_quantities' => count(array_filter(
                    $previewRows,
                    fn (array $row) => ! $row['quantity_valid']
                )),
                'total_units' => array_reduce(
                    $validRows,
                    fn (float $total, array $row) => $total + ($row['quantity'] ?? 0),
                    0.0
                ),
            ],
            'completed' => false,
        ]);

        return redirect()->route('sales.import.preview.show', ['token' => $token]);
    }

    public function showImportPreview(Request $request, string $token)
    {
        $preview = $request->session()->get('sales_imports.'.$token);

        abort_unless(
            is_array($preview)
                && ($preview['user_id'] ?? null) === $request->user()->id,
            404
        );

        return view('sales.import-preview', [
            'token' => $token,
            'rows' => $preview['rows'],
            'previewRows' => $preview['preview_rows'],
            'rowErrors' => $preview['errors'],
            'saleDate' => $preview['sale_date'],
            'emptyRowsSkipped' => $preview['empty_rows_skipped'],
            'summary' => $preview['summary'],
            'completed' => $preview['completed'],
        ]);
    }

    public function commitImport(Request $request)
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'size:40'],
        ]);

        $sessionKey = 'sales_imports.'.$validated['token'];
        $preview = $request->session()->get($sessionKey);

        abort_unless(
            is_array($preview)
                && ($preview['user_id'] ?? null) === $request->user()->id,
            404
        );

        if ($preview['completed'] ?? false) {
            return redirect()
                ->route('sales.index')
                ->with('success', 'Import ini sudah diproses; tidak ada transaksi tambahan.');
        }

        if ($preview['errors'] !== [] || $preview['rows'] === []) {
            return redirect()->route('sales.import.preview.show', [
                'token' => $validated['token'],
            ]);
        }

        try {
            $this->processSaleTransaction([
                'idempotency_key' => $preview['idempotency_key'],
                'sale_date' => $preview['sale_date'],
                'notes' => 'Import hasil penjualan Excel.',
                'details' => array_map(
                    fn (array $row) => [
                        'product_id' => $row['product_id'],
                        'quantity' => $row['total_quantity'],
                    ],
                    $preview['rows']
                ),
            ]);
        } catch (ValidationException $exception) {
            return redirect()
                ->route('sales.import.preview.show', ['token' => $validated['token']])
                ->withErrors($exception->errors());
        }

        $preview['completed'] = true;
        $request->session()->put($sessionKey, $preview);

        return redirect()
            ->route('sales.index')
            ->with('success', 'Import penjualan berhasil diproses dan stok bahan baku telah diperbarui.');
    }

    /**
     * Menyimpan transaksi penjualan dan mengurangi stok bahan baku.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'idempotency_key' => ['required', 'uuid'],
            'sale_date' => 'required|date',
            'notes' => 'nullable|string',
            'details' => 'required|array|min:1',
            'details.*.product_id' => 'required|distinct|exists:products,id',
            'details.*.quantity' => 'required|numeric|gt:0',
        ]);

        $this->processSaleTransaction($validated);

        return redirect()
            ->route('sales.index')
            ->with('success', 'Penjualan berhasil disimpan dan stok bahan baku telah diperbarui.');
    }

    private function processSaleTransaction(array $validated): void
    {
        $this->processIdempotentTransaction(
            'sales',
            $validated['idempotency_key'],
            function () use ($validated) {

                // Claim the token before waiting on stock locks so retries fail consistently.
                $saleNumber = 'PJ-'.now()->format('YmdHis')
                    .'-'.strtoupper(Str::random(4));

                $sale = Sale::create([
                    'sale_number' => $saleNumber,
                    'idempotency_key' => $validated['idempotency_key'],
                    'sale_date' => $validated['sale_date'],
                    'total_amount' => 0,
                    'notes' => $validated['notes'] ?? null,
                ]);

                $productIds = collect($validated['details'])
                    ->pluck('product_id');

                $products = Product::whereIn('id', $productIds)
                    ->where('is_active', true)
                    ->with('recipe.details.ingredient')
                    ->get()
                    ->keyBy('id');

                if ($products->count() !== $productIds->count()) {
                    throw ValidationException::withMessages([
                        'details' => 'Terdapat produk yang tidak aktif atau tidak ditemukan.',
                    ]);
                }

                // Menghitung kebutuhan bahan baku seluruh produk.
                $ingredientRequirements = [];

                foreach ($validated['details'] as $detail) {
                    $product = $products->get($detail['product_id']);
                    $recipe = $product->recipe;

                    if (! $recipe || ! $recipe->is_active || $recipe->details->isEmpty()) {
                        throw ValidationException::withMessages([
                            'details' => 'Produk '.$product->name.' belum memiliki resep aktif.',
                        ]);
                    }

                    foreach ($recipe->details as $recipeDetail) {
                        $ingredient = $recipeDetail->ingredient;

                        if (! $ingredient || ! $ingredient->is_active) {
                            throw ValidationException::withMessages([
                                'details' => 'Terdapat bahan baku tidak aktif pada resep '.$product->name.'.',
                            ]);
                        }

                        $requiredQuantity = round(
                            (float) $recipeDetail->quantity * (float) $detail['quantity'],
                            3
                        );

                        $ingredientId = $ingredient->id;

                        $ingredientRequirements[$ingredientId] = round(
                            ($ingredientRequirements[$ingredientId] ?? 0) + $requiredQuantity,
                            3
                        );
                    }
                }

                // Mengunci stok agar tidak terjadi pengurangan bersamaan.
                $ingredients = Ingredient::whereIn(
                    'id',
                    array_keys($ingredientRequirements)
                )
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                // Memastikan stok seluruh bahan baku mencukupi.
                foreach ($ingredientRequirements as $ingredientId => $required) {
                    $ingredient = $ingredients->get($ingredientId);

                    if (! $ingredient || ! $ingredient->is_active) {
                        throw ValidationException::withMessages([
                            'details' => 'Bahan baku tidak aktif atau tidak ditemukan.',
                        ]);
                    }

                    if ($required > (float) $ingredient->current_stock) {
                        throw ValidationException::withMessages([
                            'details' => 'Stok '.$ingredient->name
                                .' tidak mencukupi. Dibutuhkan: '
                                .InventoryNumberFormatter::format($required)
                                .', tersedia: '
                                .InventoryNumberFormatter::format($ingredient->current_stock),
                        ]);
                    }
                }

                // Menyimpan detail penjualan.
                $totalAmount = 0;

                foreach ($validated['details'] as $detail) {
                    $product = $products->get($detail['product_id']);

                    $quantity = (float) $detail['quantity'];
                    $unitPrice = (float) $product->selling_price;
                    $subtotal = $quantity * $unitPrice;

                    $sale->details()->create([
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'subtotal' => $subtotal,
                    ]);

                    $totalAmount += $subtotal;
                }

                // Mengurangi stok bahan baku dan mencatat riwayatnya.
                foreach ($ingredientRequirements as $ingredientId => $required) {
                    $ingredient = $ingredients->get($ingredientId);

                    $stockBefore = (float) $ingredient->current_stock;
                    $stockAfter = round($stockBefore - $required, 3);

                    $ingredient->update([
                        'current_stock' => $stockAfter,
                    ]);

                    StockMovement::create([
                        'ingredient_id' => $ingredient->id,
                        'type' => 'sale',
                        'quantity' => $required,
                        'stock_before' => $stockBefore,
                        'stock_after' => $stockAfter,
                        'reference_number' => $saleNumber,
                        'notes' => 'Pengurangan stok otomatis dari transaksi penjualan.',
                    ]);
                }

                // Memperbarui total transaksi.
                $sale->update([
                    'total_amount' => $totalAmount,
                ]);
            }
        );
    }

    private function isEmptyImportRow(array $values): bool
    {
        foreach ($values as $value) {
            if ($value !== null && trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function normalizeProductName(string $value): string
    {
        $collapsed = preg_replace('/\s+/u', ' ', trim($value));

        return mb_strtolower($collapsed ?? trim($value));
    }
}

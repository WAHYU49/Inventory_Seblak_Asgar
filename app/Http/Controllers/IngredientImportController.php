<?php

namespace App\Http\Controllers;

use App\Imports\IngredientMasterImport;
use App\Models\Ingredient;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Exceptions\NoTypeDetectedException;
use PhpOffice\PhpSpreadsheet\Reader\Exception as SpreadsheetReaderException;

class IngredientImportController extends Controller
{
    private const HEADER_MAP = [
        'sku_id' => 'code',
        'nama_barang' => 'name',
        'kategori' => 'category',
        'satuan' => 'unit',
        'jumlah_isi' => 'package_quantity',
        'het' => 'het_price',
        'hpp_satuan' => 'hpp_price',
        'supplier' => 'supplier',
        'alokasi' => 'allocation',
    ];

    public function index()
    {
        return view('ingredients.import');
    }

    public function preview(Request $request)
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:5120'],
        ]);

        $import = new IngredientMasterImport();

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

        /*
        |--------------------------------------------------------------------------
        | Mencari Header Secara Otomatis
        |--------------------------------------------------------------------------
        | Header workbook asli berada di baris kedua.
        | Sistem mencari header pada 20 baris pertama agar tetap fleksibel.
        */

        $headerPosition = null;
        $headerIndexes = [];
        $bestHeaderIndexes = [];

        foreach ($rows->take(20) as $position => $headerRow) {
            $candidate = $this->headerIndexes($headerRow);

            if (count($candidate) > count($bestHeaderIndexes)) {
                $bestHeaderIndexes = $candidate;
            }

            $missing = array_diff(
                array_keys(self::HEADER_MAP),
                array_keys($candidate)
            );

            if ($missing === []) {
                $headerPosition = $position;
                $headerIndexes = $candidate;
                break;
            }
        }

        if ($headerPosition === null) {
            $missingHeaders = array_diff(
                array_keys(self::HEADER_MAP),
                array_keys($bestHeaderIndexes)
            );

            return back()->withErrors([
                'file' => 'Header wajib tidak ditemukan: '
                    . implode(', ', $missingHeaders) . '.',
            ]);
        }

        // Data dimulai setelah baris header ditemukan.
        $dataRows = $rows->slice($headerPosition + 1)->values();

        if ($dataRows->count() > 5000) {
            return back()->withErrors([
                'file' => 'Maksimal 5.000 baris data dapat diproses dalam satu import.',
            ]);
        }

        $validRows = [];
        $rowErrors = [];
        $skipped = 0;
        $seenCodes = [];

        $existingIngredients = Ingredient::query()
            ->get(['code', 'unit'])
            ->keyBy(fn(Ingredient $ingredient) => mb_strtolower($ingredient->code));

        foreach ($dataRows as $index => $row) {
            // Nomor baris mengikuti posisi asli pada Excel.
            $rowNumber = $headerPosition + $index + 2;
            $values = $row->all();

            // Lewati baris kosong dan judul kelompok bahan.
            if (
                $this->isEmptyRow($values)
                || $this->isSectionHeadingRow($values, $headerIndexes)
            ) {
                $skipped++;
                continue;
            }

            $attributes = [];

            foreach (self::HEADER_MAP as $header => $attribute) {
                $attributes[$attribute] = $values[$headerIndexes[$header]] ?? null;
            }

            $attributes['code'] = trim((string) ($attributes['code'] ?? ''));
            $attributes['name'] = trim((string) ($attributes['name'] ?? ''));
            $attributes['unit'] = trim((string) ($attributes['unit'] ?? ''));

            $errors = [];

            // Validasi SKU ID.
            if ($attributes['code'] === '') {
                $errors[] = 'SKU ID wajib diisi.';
            } elseif (mb_strlen($attributes['code']) > 50) {
                $errors[] = 'SKU ID maksimal 50 karakter.';
            }

            // Validasi nama barang.
            if ($attributes['name'] === '') {
                $errors[] = 'Nama Barang wajib diisi.';
            } elseif (mb_strlen($attributes['name']) > 255) {
                $errors[] = 'Nama Barang maksimal 255 karakter.';
            }

            // Validasi satuan.
            if ($attributes['unit'] === '') {
                $errors[] = 'Satuan wajib diisi.';
            } elseif (mb_strlen($attributes['unit']) > 30) {
                $errors[] = 'Satuan maksimal 30 karakter.';
            }

            // Validasi kolom teks opsional.
            foreach (['category', 'supplier', 'allocation'] as $field) {
                $attributes[$field] = $this->nullableString($attributes[$field]);

                if (
                    $attributes[$field] !== null
                    && mb_strlen($attributes[$field]) > 255
                ) {
                    $errors[] = ucfirst($field) . ' maksimal 255 karakter.';
                }
            }

            // Validasi angka.
            foreach (['package_quantity', 'het_price', 'hpp_price'] as $field) {
                [$number, $valid] = $this->parseNumber($attributes[$field]);

                $attributes[$field] = $number;

                if (!$valid) {
                    $errors[] = $this->numericFieldLabel($field)
                        . ' harus berupa angka non-negatif.';

                    continue;
                }

                if ($number !== null) {
                    $maximum = $field === 'package_quantity'
                        ? 999999999.999
                        : 9999999999.99;

                    $scale = $field === 'package_quantity' ? 3 : 2;

                    if (
                        $number > $maximum
                        || abs($number - round($number, $scale)) > 0.00000001
                    ) {
                        $errors[] = $this->numericFieldLabel($field)
                            . ' melebihi batas digit yang didukung.';
                    }
                }
            }

            // Cegah SKU duplikat dalam satu file.
            $normalizedCode = mb_strtolower($attributes['code']);

            if (
                $normalizedCode !== ''
                && isset($seenCodes[$normalizedCode])
            ) {
                $errors[] = 'SKU ID duplikat di dalam file (baris '
                    . $seenCodes[$normalizedCode] . ').';
            } elseif ($normalizedCode !== '') {
                $seenCodes[$normalizedCode] = $rowNumber;
            }

            // Cegah perubahan satuan pada bahan yang sudah digunakan.
            $existingIngredient = $existingIngredients->get($normalizedCode);

            if (
                $existingIngredient
                && mb_strtolower(trim($existingIngredient->unit))
                !== mb_strtolower($attributes['unit'])
            ) {
                $errors[] = 'Satuan berbeda dari data yang sudah tersimpan. '
                    . 'Periksa dan selaraskan satuan secara manual sebelum import.';
            }

            if ($errors !== []) {
                $rowErrors[] = [
                    'row' => $rowNumber,
                    'message' => implode(' ', $errors),
                ];

                continue;
            }

            $attributes['row_number'] = $rowNumber;

            $validRows[] = $attributes;
        }

        // Hitung jumlah data baru dan data yang diperbarui.
        $newCount = 0;
        $updatedCount = 0;

        foreach ($validRows as $row) {
            if ($existingIngredients->has(mb_strtolower($row['code']))) {
                $updatedCount++;
            } else {
                $newCount++;
            }
        }

        // Simpan hasil preview sementara di session.
        $token = Str::random(40);

        $request->session()->put('ingredient_master_imports.' . $token, [
            'user_id' => $request->user()->id,
            'rows' => $validRows,
            'errors' => $rowErrors,
            'skipped' => $skipped,
        ]);

        return view('ingredients.import-preview', [
            'token' => $token,
            'rows' => $validRows,
            'rowErrors' => $rowErrors,
            'skipped' => $skipped,
            'newCount' => $newCount,
            'updatedCount' => $updatedCount,
        ]);
    }

    public function commit(Request $request)
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'size:40'],
        ]);

        $sessionKey = 'ingredient_master_imports.' . $validated['token'];

        $preview = $request->session()->get($sessionKey);

        abort_unless(
            is_array($preview)
                && ($preview['user_id'] ?? null) === $request->user()->id,
            404
        );

        if (empty($preview['rows'])) {
            return redirect()
                ->route('ingredients.import')
                ->with('error', 'Tidak ada baris valid untuk disimpan.');
        }

        $created = 0;
        $updated = 0;

        try {
            DB::transaction(function () use ($preview, &$created, &$updated) {
                foreach ($preview['rows'] as $row) {
                    $ingredient = Ingredient::query()
                        ->whereRaw(
                            'LOWER(code) = ?',
                            [mb_strtolower($row['code'])]
                        )
                        ->lockForUpdate()
                        ->first();

                    $attributes = $row;

                    unset($attributes['row_number']);

                    if ($ingredient) {
                        // Pertahankan SKU dan satuan yang sudah tersimpan.
                        unset($attributes['code'], $attributes['unit']);

                        // Nilai kosong tidak menimpa data lama.
                        $attributes = array_filter(
                            $attributes,
                            fn($value) => $value !== null
                        );

                        $ingredient->update($attributes);

                        $updated++;
                    } else {
                        Ingredient::create($attributes);

                        $created++;
                    }
                }
            });
        } catch (QueryException $exception) {
            report($exception);

            return redirect()
                ->route('ingredients.import')
                ->with(
                    'error',
                    'Import gagal disimpan. Tidak ada perubahan yang diterapkan; '
                        . 'periksa data dan coba kembali.'
                );
        }

        $request->session()->forget($sessionKey);

        return redirect()
            ->route('ingredients.import')
            ->with('import_report', [
                'created' => $created,
                'updated' => $updated,
                'skipped' => $preview['skipped'],
                'failed' => count($preview['errors']),
                'errors' => $preview['errors'],
            ]);
    }

    /**
     * Mencocokkan nama header Excel dengan kolom yang dibutuhkan.
     */
    private function headerIndexes($headerRow): array
    {
        $indexes = [];

        foreach ($headerRow as $index => $value) {
            $normalized = Str::slug(trim((string) $value), '_');

            if (array_key_exists($normalized, self::HEADER_MAP)) {
                $indexes[$normalized] = $index;
            }
        }

        return $indexes;
    }

    /**
     * Memeriksa apakah satu baris benar-benar kosong.
     */
    private function isEmptyRow(array $values): bool
    {
        foreach ($values as $value) {
            if ($value !== null && trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * Melewati baris judul kelompok seperti TOPPING SEBLAK PRASMANAN.
     */
    private function isSectionHeadingRow(
        array $values,
        array $headerIndexes
    ): bool {
        $nonEmptyIndexes = [];

        foreach ($values as $index => $value) {
            if ($value !== null && trim((string) $value) !== '') {
                $nonEmptyIndexes[] = $index;
            }
        }

        return count($nonEmptyIndexes) === 1
            && !in_array(
                $nonEmptyIndexes[0],
                array_values($headerIndexes),
                true
            );
    }

    /**
     * Mengubah string kosong menjadi NULL.
     */
    private function nullableString(mixed $value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        return trim((string) $value);
    }

    /**
     * Membaca angka dari Excel, termasuk format Rupiah.
     */
    private function parseNumber(mixed $value): array
    {
        if ($value === null || trim((string) $value) === '') {
            return [null, true];
        }

        if (is_string($value)) {
            $value = trim(str_ireplace(['rp', ' '], '', $value));

            if (
                str_contains($value, ',')
                && str_contains($value, '.')
            ) {
                if (strrpos($value, ',') > strrpos($value, '.')) {
                    $value = str_replace('.', '', $value);
                    $value = str_replace(',', '.', $value);
                } else {
                    $value = str_replace(',', '', $value);
                }
            } elseif (str_contains($value, ',')) {
                $value = str_replace(',', '.', $value);
            }
        }

        if (
            !is_numeric($value)
            || !is_finite((float) $value)
            || (float) $value < 0
        ) {
            return [null, false];
        }

        return [(float) $value, true];
    }

    /**
     * Nama kolom untuk pesan validasi angka.
     */
    private function numericFieldLabel(string $field): string
    {
        return match ($field) {
            'package_quantity' => 'Jumlah / Isi',
            'het_price' => 'HET',
            'hpp_price' => 'HPP satuan',
        };
    }
}

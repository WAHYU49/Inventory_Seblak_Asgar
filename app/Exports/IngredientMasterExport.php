<?php

namespace App\Exports;

use App\Models\Ingredient;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class IngredientMasterExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    public function query(): Builder
    {
        return Ingredient::query()->orderBy('code');
    }

    public function headings(): array
    {
        return [
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
        ];
    }

    public function map($ingredient): array
    {
        return [
            $ingredient->code,
            $ingredient->name,
            $ingredient->category,
            $ingredient->unit,
            $ingredient->package_quantity,
            $ingredient->supplier,
            $ingredient->allocation,
            $ingredient->purchase_unit,
            $ingredient->current_stock,
            $ingredient->minimum_stock,
            $ingredient->is_active ? 'Aktif' : 'Nonaktif',
        ];
    }
}

<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

class ProductImportTemplateExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            new ProductTemplateSheetExport,
            new RecipeTemplateSheetExport,
        ];
    }
}

class ProductTemplateSheetExport implements FromArray, WithTitle
{
    public function array(): array
    {
        return [
            ['Produk', 'Kategori'],
        ];
    }

    public function title(): string
    {
        return 'PRODUK';
    }
}

class RecipeTemplateSheetExport implements FromArray, WithTitle
{
    public function array(): array
    {
        return [
            ['Produk', 'Bahan Baku', 'Jumlah', 'Satuan'],
        ];
    }

    public function title(): string
    {
        return 'RESEP MENU';
    }
}

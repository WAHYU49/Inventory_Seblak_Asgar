<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;

class ProductImportTemplateExport implements FromArray
{
    public function array(): array
    {
        return [
            ['Produk', 'Kategori'],
        ];
    }
}

<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class IngredientMasterImport implements ToCollection, WithMultipleSheets
{
    public Collection $rows;

    public function __construct()
    {
        $this->rows = collect();
    }

    public function sheets(): array
    {
        return [
            'MASTER BAHAN BAKU' => $this,
        ];
    }

    public function collection(Collection $rows): void
    {
        $this->rows = $rows;
    }
}

<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ProductMasterImport implements WithMultipleSheets
{
    private ProductImportSheet $productSheet;

    private RecipeMenuImportSheet $recipeSheet;

    public function __construct()
    {
        $this->productSheet = new ProductImportSheet;
        $this->recipeSheet = new RecipeMenuImportSheet;
    }

    public function sheets(): array
    {
        return [
            'PRODUK' => $this->productSheet,
            'RESEP MENU' => $this->recipeSheet,
        ];
    }

    public function productRows(): Collection
    {
        return $this->productSheet->rows;
    }

    public function recipeRows(): Collection
    {
        return $this->recipeSheet->rows;
    }
}

class ProductImportSheet implements ToCollection
{
    public Collection $rows;

    public function __construct()
    {
        $this->rows = collect();
    }

    public function collection(Collection $rows): void
    {
        $this->rows = $rows;
    }
}

class RecipeMenuImportSheet implements ToCollection
{
    public Collection $rows;

    public function __construct()
    {
        $this->rows = collect();
    }

    public function collection(Collection $rows): void
    {
        $this->rows = $rows;
    }
}

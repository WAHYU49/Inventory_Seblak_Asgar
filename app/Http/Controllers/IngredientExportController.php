<?php

namespace App\Http\Controllers;

use App\Exports\IngredientMasterExport;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class IngredientExportController extends Controller
{
    public function __invoke(): BinaryFileResponse
    {
        $filename = 'master_bahan_baku_'.now()->format('Ymd_His').'.xlsx';

        return Excel::download(
            new IngredientMasterExport,
            $filename,
            \Maatwebsite\Excel\Excel::XLSX
        );
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\StockMovement;
use Illuminate\Http\Request;

class StockMovementController extends Controller
{
    public function index(Request $request)
    {
        $query = StockMovement::with('ingredient')
            ->latest();

        // Filter berdasarkan nama atau kode bahan baku
        if ($request->filled('search')) {
            $search = $request->search;

            $query->whereHas('ingredient', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // Filter berdasarkan jenis pergerakan stok
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter berdasarkan rentang tanggal
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $movements = $query->paginate(10)->withQueryString();

        return view('stock_movements.index', compact('movements'));
    }
}

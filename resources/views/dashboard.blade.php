@extends('layouts.app')

@section('title', 'Dashboard - Inventory Seblak Asgar')

@section('content')

<div class="page-header">
    <h1>Dashboard</h1>
    <p>Selamat datang di Sistem Inventory Seblak Asgar.</p>
</div>

<style>
    .dashboard-cards {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        margin-bottom: 30px;
    }

    .dashboard-card {
        background: white;
        padding: 22px;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        border-left: 5px solid #3d7956;
    }

    .dashboard-card h3 {
        margin: 0 0 12px;
        font-size: 14px;
        color: #718078;
        font-weight: normal;
    }

    .dashboard-card .number {
        font-size: 30px;
        font-weight: bold;
        color: #183b2b;
    }

    .dashboard-section {
        background: white;
        padding: 22px;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        margin-bottom: 25px;
    }

    .dashboard-section h2 {
        margin-top: 0;
        font-size: 18px;
    }

    .dashboard-table {
        width: 100%;
        border-collapse: collapse;
    }

    .dashboard-table th,
    .dashboard-table td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #eee;
    }

    .dashboard-table th {
        background: #f4f6f5;
        color: #526258;
        font-size: 13px;
    }

    .stock-warning {
        color: #c0392b;
        font-weight: bold;
    }

    .stock-safe {
        color: #27834c;
        font-weight: bold;
    }

    @media (max-width: 1000px) {
        .dashboard-cards {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 600px) {
        .dashboard-cards {
            grid-template-columns: 1fr;
        }
    }

    .stock-chart {
    background: white;
    padding: 22px;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    margin-bottom: 25px;
}

.stock-chart h2 {
    margin-top: 0;
    font-size: 18px;
}

.chart-item {
    margin-bottom: 20px;
}

.chart-label {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
    font-size: 14px;
}

.chart-track {
    height: 13px;
    background: #edf0ee;
    border-radius: 10px;
    overflow: hidden;
}

.chart-fill {
    height: 100%;
    border-radius: 10px;
}
</style>

<div class="dashboard-cards">

    <div class="dashboard-card">
        <h3>Total Produk Menu</h3>
        <div class="number">{{ $totalProducts }}</div>
    </div>

    <div class="dashboard-card">
        <h3>Total Bahan Baku Aktif</h3>
        <div class="number">{{ $totalIngredients }}</div>
    </div>

    <div class="dashboard-card">
        <h3>Total Resep Aktif</h3>
        <div class="number">{{ $totalRecipes }}</div>
    </div>

    <div class="dashboard-card" style="border-left-color: #c0392b;">
    <h3>Stok Habis</h3>
    <div class="number" style="color: #c0392b;">
        {{ $outOfStockIngredients }}
    </div>
</div>

<div class="dashboard-card" style="border-left-color: #e67e22;">
    <h3>Stok Menipis</h3>
    <div class="number" style="color: #e67e22;">
        {{ $lowStockIngredients }}
    </div>
</div>

<div class="dashboard-card" style="border-left-color: #27834c;">
    <h3>Stok Aman</h3>
    <div class="number" style="color: #27834c;">
        {{ $safeStockIngredients }}
    </div>
</div>

</div>

<div class="stock-chart">
    <h2>Grafik Kondisi Stok Bahan Baku</h2>

    @php
        $totalStockCategories = $outOfStockIngredients
            + $lowStockIngredients
            + $safeStockIngredients;

        $outPercentage = $totalStockCategories > 0
            ? ($outOfStockIngredients / $totalStockCategories) * 100 : 0;

        $lowPercentage = $totalStockCategories > 0
            ? ($lowStockIngredients / $totalStockCategories) * 100 : 0;

        $safePercentage = $totalStockCategories > 0
            ? ($safeStockIngredients / $totalStockCategories) * 100 : 0;
    @endphp

    <div class="chart-item">
        <div class="chart-label">
            <span>Stok Habis</span>
            <strong>{{ $outOfStockIngredients }}</strong>
        </div>
        <div class="chart-track">
            <div class="chart-fill"
                style="width: {{ $outPercentage }}%; background: #c0392b;">
            </div>
        </div>
    </div>

    <div class="chart-item">
        <div class="chart-label">
            <span>Stok Menipis</span>
            <strong>{{ $lowStockIngredients }}</strong>
        </div>
        <div class="chart-track">
            <div class="chart-fill"
                style="width: {{ $lowPercentage }}%; background: #e67e22;">
            </div>
        </div>
    </div>

    <div class="chart-item">
        <div class="chart-label">
            <span>Stok Aman</span>
            <strong>{{ $safeStockIngredients }}</strong>
        </div>
        <div class="chart-track">
            <div class="chart-fill"
                style="width: {{ $safePercentage }}%; background: #27834c;">
            </div>
        </div>
    </div>
</div>

<div class="dashboard-section">
    <h2>Daftar Bahan Baku dengan Stok Menipis</h2>

    @if ($lowStockList->count() > 0)

        <table class="dashboard-table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Kode Bahan</th>
                    <th>Nama Bahan</th>
                    <th>Stok Saat Ini</th>
                    <th>Minimum Stok</th>
                    <th>Status</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($lowStockList as $index => $ingredient)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $ingredient->code }}</td>
                        <td>{{ $ingredient->name }}</td>
                        <td>
                            {{ $ingredient->current_stock }}
                            {{ $ingredient->unit }}
                        </td>
                        <td>
                            {{ $ingredient->minimum_stock }}
                            {{ $ingredient->unit }}
                        </td>
                        <td class="stock-warning">Stok Menipis</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

    @else

        <p class="stock-safe">
            Semua bahan baku masih berada di atas batas minimum stok.
        </p>

    @endif
</div>

@endsection
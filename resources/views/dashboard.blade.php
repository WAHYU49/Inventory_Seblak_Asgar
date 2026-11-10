@extends('layouts.app')

@section('title', 'Dashboard - Inventory Seblak Asgar')

@section('content')

<div class="page-header">
    <h1>Dashboard</h1>
    <p>Selamat datang di Sistem Inventory Seblak Asgar.</p>
</div>

@if ($errors->any())
    <div style="background:#fde8e8; color:#a52828; padding:12px;
        border-radius:6px; margin-bottom:15px;">
        @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

<div class="dashboard-section">
    <form action="{{ route('dashboard') }}" method="GET"
        style="display:flex; flex-wrap:wrap; gap:12px; align-items:end;">
        <div>
            <label for="dashboard-start-date">Tanggal Mulai</label><br>
            <input id="dashboard-start-date" type="date" name="start_date" value="{{ $startDate }}">
        </div>
        <div>
            <label for="dashboard-end-date">Tanggal Akhir</label><br>
            <input id="dashboard-end-date" type="date" name="end_date" value="{{ $endDate }}">
        </div>
        <button type="submit">Filter</button>
        <a href="{{ route('dashboard') }}"
            style="background:#eee; color:#333; padding:7px 12px; border-radius:4px; text-decoration:none;">
            Reset
        </a>
    </form>
</div>

<style>
    .dashboard-cards {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        margin-bottom: 30px;
    }

    .dashboard-card {
        display: block;
        background: white;
        padding: 22px;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        border-left: 5px solid #3d7956;
        color: inherit;
        text-decoration: none;
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

    <a class="dashboard-card" href="{{ route('products.index') }}">
        <h3>Total Produk Menu</h3>
        <div class="number">{{ $totalProducts }}</div>
    </a>

    <a class="dashboard-card" href="{{ route('ingredients.index') }}">
        <h3>Total Bahan Baku Aktif</h3>
        <div class="number">{{ $totalIngredients }}</div>
    </a>

    <a class="dashboard-card" href="{{ route('recipes.index') }}">
        <h3>Total Resep Aktif</h3>
        <div class="number">{{ $totalRecipes }}</div>
    </a>

    <a class="dashboard-card" href="{{ route('ingredients.index', ['stock_status' => 'empty']) }}"
        style="border-left-color: #c0392b;">
        <h3>Stok Habis</h3>
        <div class="number" style="color: #c0392b;">
            {{ $outOfStockIngredients }}
        </div>
    </a>

    <a class="dashboard-card" href="{{ route('ingredients.index', ['stock_status' => 'low']) }}"
        style="border-left-color: #e67e22;">
        <h3>Stok Menipis</h3>
        <div class="number" style="color: #e67e22;">
            {{ $lowStockIngredients }}
        </div>
    </a>

    <a class="dashboard-card" href="{{ route('ingredients.index', ['stock_status' => 'safe']) }}"
        style="border-left-color: #27834c;">
        <h3>Stok Aman</h3>
        <div class="number" style="color: #27834c;">
            {{ $safeStockIngredients }}
        </div>
    </a>

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
    <h2>10 Bahan Baku dengan Stok Menipis</h2>

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
                    <th>Tanggal Input</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($lowStockList as $index => $ingredient)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $ingredient->code }}</td>
                        <td>{{ $ingredient->name }}</td>
                        <td>
                            {{ \App\Support\InventoryNumberFormatter::format($ingredient->current_stock) }}
                            {{ $ingredient->unit }}
                        </td>
                        <td>
                            {{ \App\Support\InventoryNumberFormatter::format($ingredient->minimum_stock) }}
                            {{ $ingredient->unit }}
                        </td>
                        <td class="stock-warning">Stok Menipis</td>
                        <td>{{ $ingredient->created_at->format('d-m-Y H:i') }}</td>
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

<div class="dashboard-section">
    <div style="display:flex; justify-content:space-between; align-items:center; gap:10px;">
        <h2>Pembelian ({{ $totalPurchases }} transaksi)</h2>
        <a href="{{ route('purchases.index') }}">Lihat Semua</a>
    </div>

    @if ($recentPurchases->isEmpty())
        <p>Belum ada aktivitas pembelian pada periode ini.</p>
    @else
        <div style="overflow-x:auto;">
            <table class="dashboard-table">
                <thead>
                    <tr>
                        <th>Tanggal Transaksi</th>
                        <th>Nomor Pembelian</th>
                        <th>Bahan dan Kuantitas</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($recentPurchases as $purchase)
                        <tr>
                            <td>{{ $purchase->purchase_date->format('d-m-Y') }}</td>
                            <td>{{ $purchase->purchase_number }}</td>
                            <td>
                                @foreach ($purchase->details as $detail)
                                    <div>
                                        {{ $detail->ingredient->name ?? 'Bahan tidak ditemukan' }}:
                                        {{ \App\Support\InventoryNumberFormatter::format($detail->quantity) }}
                                        {{ $detail->ingredient->unit ?? '' }}
                                    </div>
                                @endforeach
                            </td>
                            <td>{{ $purchase->notes ?: 'Pembelian bahan baku' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<div class="dashboard-section">
    <div style="display:flex; justify-content:space-between; align-items:center; gap:10px;">
        <h2>Penjualan ({{ $totalSales }} transaksi)</h2>
        <a href="{{ route('sales.index') }}">Lihat Semua</a>
    </div>

    @if ($recentSales->isEmpty())
        <p>Belum ada aktivitas penjualan pada periode ini.</p>
    @else
        <div style="overflow-x:auto;">
            <table class="dashboard-table">
                <thead>
                    <tr>
                        <th>Tanggal Transaksi</th>
                        <th>Nomor Penjualan</th>
                        <th>Produk dan Kuantitas</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($recentSales as $sale)
                        <tr>
                            <td>{{ $sale->sale_date->format('d-m-Y') }}</td>
                            <td>{{ $sale->sale_number }}</td>
                            <td>
                                @foreach ($sale->details as $detail)
                                    <div>
                                        {{ $detail->product->name ?? 'Produk tidak ditemukan' }}:
                                        {{ \App\Support\InventoryNumberFormatter::format($detail->quantity) }}
                                    </div>
                                @endforeach
                            </td>
                            <td>{{ $sale->notes ?: 'Penjualan produk' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<div class="dashboard-section">
    <div style="display:flex; justify-content:space-between; align-items:center; gap:10px;">
        <h2>Penyesuaian Stok ({{ $totalAdjustments }} aktivitas)</h2>
        <a href="{{ route('stock-adjustments.index') }}">Lihat Semua</a>
    </div>

    @if ($recentAdjustments->isEmpty())
        <p>Belum ada aktivitas penyesuaian pada periode ini.</p>
    @else
        <div style="overflow-x:auto;">
            <table class="dashboard-table">
                <thead>
                    <tr>
                        <th>Tanggal Transaksi</th>
                        <th>Nomor Penyesuaian</th>
                        <th>Bahan Baku</th>
                        <th>Jenis dan Jumlah</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($recentAdjustments as $adjustment)
                        <tr>
                            <td>{{ $adjustment->adjustment_date->format('d-m-Y') }}</td>
                            <td>{{ $adjustment->adjustment_number }}</td>
                            <td>{{ $adjustment->ingredient->name ?? 'Bahan tidak ditemukan' }}</td>
                            <td>
                                {{ $adjustment->type === 'increase' ? 'Penambahan' : 'Pengurangan' }}:
                                {{ \App\Support\InventoryNumberFormatter::format($adjustment->quantity) }}
                                {{ $adjustment->ingredient->unit ?? '' }}
                            </td>
                            <td>{{ $adjustment->reason }}{{ $adjustment->notes ? ' - ' . $adjustment->notes : '' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

@endsection
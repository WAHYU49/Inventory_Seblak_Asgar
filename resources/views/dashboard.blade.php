@extends('layouts.app')

@section('title', 'Dashboard - Inventory Seblak Asgar')

@section('content')

<div class="page-header">
    <h1>Dashboard</h1>
    <p>Selamat datang di Sistem Inventory Seblak Asgar.</p>
</div>

@if ($errors->any())
    <div class="alert alert-danger" role="alert">
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
        <button type="submit" class="btn btn-primary">Filter</button>
        <a href="{{ route('dashboard') }}" class="btn btn-secondary">Reset</a>
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

    .dashboard-section h2 {
        margin-top: 0;
        font-size: 18px;
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
        color: #26352c;
    }

    .stock-chart-header {
        margin-bottom: 22px;
    }

    .stock-chart-header h2 {
        margin: 0 0 6px;
        color: #183b2b;
        font-size: 19px;
    }

    .stock-chart-description {
        margin: 0;
        color: #718078;
        font-size: 14px;
    }

    .stock-chart-layout {
        display: grid;
        grid-template-columns: minmax(0, 0.9fr) minmax(0, 1.1fr);
        align-items: center;
        gap: 30px;
    }

    .stock-donut-area {
        position: relative;
        width: min(100%, 260px);
        aspect-ratio: 1;
        margin-inline: auto;
    }

    .stock-donut {
        display: block;
        width: 100%;
        height: 100%;
        overflow: visible;
    }

    .stock-donut-track,
    .stock-donut-segment {
        fill: none;
        stroke-width: 16;
    }

    .stock-donut-track {
        stroke: #edf1ee;
    }

    .stock-donut-segment {
        stroke-linecap: butt;
    }

    .stock-donut-center {
        position: absolute;
        inset: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        pointer-events: none;
        text-align: center;
    }

    .stock-donut-total {
        color: #183b2b;
        font-size: 36px;
        font-weight: 700;
        line-height: 1;
    }

    .stock-donut-caption {
        margin-top: 7px;
        color: #718078;
        font-size: 13px;
    }

    .stock-chart-legend {
        display: grid;
        gap: 12px;
        min-width: 0;
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .stock-chart-legend-item {
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 0;
        padding: 14px 16px;
        border: 1px solid #e8ede9;
        border-radius: 9px;
        background: #fff;
    }

    .stock-chart-marker {
        flex: 0 0 11px;
        width: 11px;
        height: 11px;
        border-radius: 50%;
    }

    .stock-chart-legend-content {
        flex: 1;
        min-width: 0;
    }

    .stock-chart-legend-label {
        display: block;
        margin-bottom: 4px;
        color: #37463c;
        font-size: 14px;
        font-weight: 600;
    }

    .stock-chart-legend-stats {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        gap: 4px 12px;
        color: #718078;
        font-size: 13px;
    }

    .stock-chart-legend-stats strong {
        color: #26352c;
    }

    .stock-chart-empty {
        padding: 28px 20px;
        border: 1px dashed #d7dfd9;
        border-radius: 9px;
        background: #f8faf8;
        text-align: center;
    }

    .stock-chart-empty strong {
        display: block;
        margin-bottom: 6px;
        color: #37463c;
    }

    .stock-chart-empty p {
        margin: 0 0 14px;
        color: #718078;
        font-size: 14px;
    }

    @media (max-width: 650px) {
        .stock-chart-layout {
            grid-template-columns: minmax(0, 1fr);
            gap: 20px;
        }

        .stock-donut-area {
            width: min(100%, 230px);
        }
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

@php
    $stockChartSegments = [
        ['key' => 'safe', 'label' => 'Aman', 'count' => $safeStockIngredients, 'color' => '#27834c'],
        ['key' => 'low', 'label' => 'Stok Menipis', 'count' => $lowStockIngredients, 'color' => '#e67e22'],
        ['key' => 'empty', 'label' => 'Stok Habis', 'count' => $outOfStockIngredients, 'color' => '#c0392b'],
    ];
    $totalStockCategories = array_sum(array_column($stockChartSegments, 'count'));
    $stockChartOffset = 0;
@endphp

<section class="stock-chart"
    data-stock-total="{{ $totalIngredients }}"
    aria-labelledby="stock-chart-title"
    aria-describedby="stock-chart-description">
    <header class="stock-chart-header">
        <h2 id="stock-chart-title">Grafik Kondisi Stok Bahan Baku</h2>
        <p id="stock-chart-description" class="stock-chart-description">
            Ringkasan persediaan bahan baku aktif dibandingkan dengan batas minimum stok.
        </p>
    </header>

    @if ($totalIngredients === 0)
        <div class="stock-chart-empty" data-stock-empty="true" role="status">
            <strong>Belum ada bahan baku aktif</strong>
            <p>Data kondisi stok akan ditampilkan setelah bahan baku aktif tersedia.</p>
            <a href="{{ route('ingredients.index') }}" class="btn btn-secondary">Lihat Bahan Baku</a>
        </div>
    @else
        <div class="stock-chart-layout">
            <div class="stock-donut-area">
                <svg class="stock-donut" viewBox="0 0 120 120" role="img"
                    aria-label="Grafik donat kondisi stok untuk {{ $totalIngredients }} bahan baku aktif">
                    <circle class="stock-donut-track" cx="60" cy="60" r="44"></circle>
                    @foreach ($stockChartSegments as $segment)
                        @php
                            $percentage = $totalStockCategories > 0
                                ? ($segment['count'] / $totalStockCategories) * 100
                                : 0;
                            $formattedPercentage = rtrim(rtrim(number_format($percentage, 1, '.', ''), '0'), '.');
                        @endphp
                        <circle class="stock-donut-segment" cx="60" cy="60" r="44"
                            pathLength="100"
                            stroke="{{ $segment['color'] }}"
                            stroke-dasharray="{{ $percentage }} {{ 100 - $percentage }}"
                            stroke-dashoffset="{{ -$stockChartOffset }}"
                            transform="rotate(-90 60 60)">
                            <title>{{ $segment['label'] }}: {{ $segment['count'] }} bahan ({{ $formattedPercentage }}%)</title>
                        </circle>
                        @php
                            $stockChartOffset += $percentage;
                        @endphp
                    @endforeach
                </svg>
                <div class="stock-donut-center" aria-hidden="true">
                    <span class="stock-donut-total">{{ $totalIngredients }}</span>
                    <span class="stock-donut-caption">bahan aktif</span>
                </div>
            </div>

            <ul class="stock-chart-legend" aria-label="Rincian kondisi stok">
                @foreach ($stockChartSegments as $segment)
                    @php
                        $percentage = $totalStockCategories > 0
                            ? ($segment['count'] / $totalStockCategories) * 100
                            : 0;
                        $formattedPercentage = rtrim(rtrim(number_format($percentage, 1, '.', ''), '0'), '.');
                    @endphp
                    <li class="stock-chart-legend-item"
                        data-stock-category="{{ $segment['key'] }}"
                        data-stock-count="{{ $segment['count'] }}"
                        data-stock-percentage="{{ $formattedPercentage }}">
                        <span class="stock-chart-marker" style="background-color: {{ $segment['color'] }};"
                            aria-hidden="true"></span>
                        <span class="stock-chart-legend-content">
                            <span class="stock-chart-legend-label">{{ $segment['label'] }}</span>
                            <span class="stock-chart-legend-stats">
                                <span><strong>{{ $segment['count'] }}</strong> bahan</span>
                                <strong>{{ $formattedPercentage }}%</strong>
                            </span>
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</section>

<div class="dashboard-section">
    <h2>10 Bahan Baku dengan Stok Menipis</h2>

    @if ($lowStockList->count() > 0)

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th class="table-number">No</th>
                        <th>Kode Bahan</th>
                        <th>Nama Bahan</th>
                        <th class="table-number">Stok Saat Ini</th>
                        <th class="table-number">Minimum Stok</th>
                        <th>Status</th>
                        <th>Waktu Input (WIB)</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($lowStockList as $index => $ingredient)
                        <tr>
                            <td class="table-number">{{ $index + 1 }}</td>
                            <td>{{ $ingredient->code }}</td>
                            <td>{{ $ingredient->name }}</td>
                            <td class="table-number">
                                {{ \App\Support\InventoryNumberFormatter::format($ingredient->current_stock) }}
                                {{ $ingredient->unit }}
                            </td>
                            <td class="table-number">
                                {{ \App\Support\InventoryNumberFormatter::format($ingredient->minimum_stock) }}
                                {{ $ingredient->unit }}
                            </td>
                            <td class="stock-warning">Stok Menipis</td>
                            <td>{{ \App\Support\WibDateTimeFormatter::format($ingredient->created_at) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

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
        <div class="table-responsive">
            <table>
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
        <div class="table-responsive">
            <table>
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
        <div class="table-responsive">
            <table>
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
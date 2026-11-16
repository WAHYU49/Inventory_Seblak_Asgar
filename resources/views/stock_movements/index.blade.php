
@extends('layouts.app')

@section('title', 'Riwayat Pergerakan Stok - Inventory Seblak Asgar')

@section('content')

<div class="page-header">
    <h1>Riwayat Pergerakan Stok</h1>
    <p>Catatan seluruh perubahan stok bahan baku.</p>
</div>

<div class="content-card">
    <form action="{{ route('stock-movements.index') }}" method="GET" class="filter-form">
        <div class="filter-field filter-field-wide">
            <label for="movement-search">Cari Bahan Baku</label>
            <input id="movement-search" type="search" name="search"
                value="{{ request('search') }}" placeholder="Nama atau kode bahan...">
        </div>

        <div class="filter-field">
            <label for="movement-type">Jenis Transaksi</label>
            <select id="movement-type" name="type">
                <option value="">Semua Transaksi</option>
                <option value="purchase" {{ request('type') == 'purchase' ? 'selected' : '' }}>Pembelian</option>
                <option value="sale" {{ request('type') == 'sale' ? 'selected' : '' }}>Penjualan</option>
                <option value="adjustment" {{ request('type') == 'adjustment' ? 'selected' : '' }}>Penyesuaian</option>
                <option value="initial" {{ request('type') == 'initial' ? 'selected' : '' }}>Stok Awal</option>
            </select>
        </div>

        <div class="filter-field">
            <label for="movement-start-date">Dari Tanggal</label>
            <input id="movement-start-date" type="date" name="start_date" value="{{ request('start_date') }}">
        </div>

        <div class="filter-field">
            <label for="movement-end-date">Sampai Tanggal</label>
            <input id="movement-end-date" type="date" name="end_date" value="{{ request('end_date') }}">
        </div>

        <button type="submit" class="btn btn-primary">Cari</button>
        <a href="{{ route('stock-movements.index') }}" class="btn btn-secondary">Reset</a>
    </form>
</div>

    {{-- Tabel riwayat --}}
    <div class="table-responsive">
        <table class="table-wide">
            <thead>
                <tr>
                    <th class="table-number">No</th>
                    <th>Waktu (WIB)</th>
                    <th>Bahan Baku</th>
                    <th>Jenis</th>
                    <th class="table-number">Jumlah</th>
                    <th class="table-number">Stok Sebelum</th>
                    <th class="table-number">Stok Sesudah</th>
                    <th>No. Referensi</th>
                    <th>Keterangan</th>
                </tr>
            </thead>

            <tbody>

                @forelse ($movements as $movement)

                    <tr>

                        <td class="table-number">
                            {{ $movements->firstItem() + $loop->index }}
                        </td>

                        <td>
                            {{ \App\Support\WibDateTimeFormatter::format($movement->created_at) }}
                        </td>

                        <td>
                            <strong>{{ $movement->ingredient->name ?? 'Bahan tidak ditemukan' }}</strong>
                            <br>
                            <small>
                                {{ $movement->ingredient->code ?? '-' }}
                            </small>
                        </td>

                        <td>
                            @if ($movement->type === 'purchase')
                                <span class="badge badge-success">
                                    Pembelian
                                </span>
                            @elseif ($movement->type === 'sale')
                                <span class="badge badge-danger">
                                    Penjualan
                                </span>
                            @elseif ($movement->type === 'initial')
                                <span class="badge badge-info">
                                    Stok Awal
                                </span>
                            @else
                                <span class="badge badge-warning">
                                    Penyesuaian
                                </span>
                            @endif
                        </td>

                        <td class="table-number">
                            {{ \App\Support\InventoryNumberFormatter::format($movement->quantity) }}
                            {{ $movement->ingredient->unit ?? '' }}
                        </td>

                        <td class="table-number">
                            {{ \App\Support\InventoryNumberFormatter::format($movement->stock_before) }}
                        </td>

                        <td class="table-number">
                            {{ \App\Support\InventoryNumberFormatter::format($movement->stock_after) }}
                        </td>

                        <td>
                            {{ $movement->reference_number ?? '-' }}
                        </td>

                        <td>
                            {{ $movement->notes ?? '-' }}
                        </td>

                    </tr>

                @empty

                    <tr>
                        <td colspan="9">
                            Belum ada riwayat pergerakan stok.
                        </td>
                    </tr>

                @endforelse

            </tbody>

        </table>
    </div>

    {{-- Pagination --}}
    {{ $movements->links() }}

@endsection

@extends('layouts.app')

@section('title', 'Penjualan - Inventory Seblak Asgar')

@section('content')

<div class="page-header">
    <h1>Data Penjualan</h1>
    <p>Daftar transaksi penjualan menu Seblak Asgar.</p>
</div>

<p>
    <a href="{{ route('sales.create') }}" class="btn btn-primary">+ Tambah Penjualan</a>
    <a href="{{ route('sales.import') }}" class="btn btn-secondary">Import Hasil Penjualan</a>
</p>

@if(session('success'))
    <div class="alert alert-success" role="status">
        {{ session('success') }}
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger" role="alert">
        @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

<div class="content-card">

    <form action="{{ route('sales.index') }}" method="GET"
        class="filter-form">
        <div class="filter-field">
            <label for="sale-start-date">Dari Tanggal</label>
            <input id="sale-start-date" type="date" name="start_date" value="{{ $startDate }}">
        </div>
        <div class="filter-field">
            <label for="sale-end-date">Sampai Tanggal</label>
            <input id="sale-end-date" type="date" name="end_date" value="{{ $endDate }}">
        </div>
        <button type="submit" class="btn btn-primary">Filter</button>
        <a href="{{ route('sales.index') }}" class="btn btn-secondary">Reset</a>
    </form>

    <div class="table-responsive">
        <table>

        <thead>
            <tr>
                <th class="table-number">No.</th>
                <th>Nomor Transaksi</th>
                <th>Tanggal</th>
                <th>Waktu Input (WIB)</th>
                <th>Jumlah Produk / Detail Menu</th>
            </tr>
        </thead>

        <tbody>
            @forelse($sales as $sale)
                <tr>

                    <td class="table-number">
                        {{ $sales->firstItem() + $loop->index }}
                    </td>

                    <td>
                        {{ $sale->sale_number }}
                    </td>

                    <td>
                        {{ $sale->sale_date->format('d-m-Y') }}
                    </td>

                    <td>
                        {{ \App\Support\WibDateTimeFormatter::format($sale->created_at) }}
                    </td>

                    <td>
                        {{ $sale->details->count() }} produk
                        @foreach($sale->details as $detail)
                            <div>
                                {{ $detail->product->name ?? 'Produk tidak tersedia' }}
                                <span>
                                    ({{ \App\Support\InventoryNumberFormatter::format($detail->quantity) }} x)
                                </span>
                            </div>
                        @endforeach
                    </td>

                </tr>
            @empty
                <tr>
                    <td colspan="5">
                        Belum ada transaksi penjualan.
                    </td>
                </tr>
            @endforelse
        </tbody>

        </table>
    </div>

    {{ $sales->links() }}

</div>

@endsection
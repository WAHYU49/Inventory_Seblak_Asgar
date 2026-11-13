@extends('layouts.app')

@section('content')

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:25px;">
    <div>
        <h2 style="color:#183b2b; margin-bottom:5px;">Data Penjualan</h2>
        <p style="color:#777; font-size:14px;">
            Daftar transaksi penjualan menu Seblak Asgar.
        </p>
    </div>

    <a href="{{ route('sales.create') }}" class="btn btn-primary">
        + Tambah Penjualan
    </a>
</div>

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
        style="display:flex; flex-wrap:wrap; gap:12px; align-items:end; margin-bottom:20px;">
        <div>
            <label for="sale-start-date">Dari Tanggal</label><br>
            <input id="sale-start-date" type="date" name="start_date" value="{{ $startDate }}">
        </div>
        <div>
            <label for="sale-end-date">Sampai Tanggal</label><br>
            <input id="sale-end-date" type="date" name="end_date" value="{{ $endDate }}">
        </div>
        <button type="submit" class="btn btn-primary">Filter</button>
        <a href="{{ route('sales.index') }}" class="btn btn-secondary">Reset</a>
    </form>

    <div class="table-responsive">
    <table style="width:100%; border-collapse:collapse; font-size:14px;">

        <thead>
            <tr style="background:#f3f6f2; color:#183b2b;">
                <th style="padding:13px; text-align:left;">No.</th>
                <th style="padding:13px; text-align:left;">Nomor Transaksi</th>
                <th style="padding:13px; text-align:left;">Tanggal</th>
                <th style="padding:13px; text-align:left;">Waktu Input (WIB)</th>
                <th style="padding:13px; text-align:left;">Jumlah Produk / Detail Menu</th>
            </tr>
        </thead>

        <tbody>
            @forelse($sales as $sale)
                <tr style="border-bottom:1px solid #eee;">

                    <td style="padding:13px;">
                        {{ $sales->firstItem() + $loop->index }}
                    </td>

                    <td style="padding:13px; font-weight:bold; color:#183b2b;">
                        {{ $sale->sale_number }}
                    </td>

                    <td style="padding:13px;">
                        {{ $sale->sale_date->format('d-m-Y') }}
                    </td>

                    <td style="padding:13px;">
                        {{ \App\Support\WibDateTimeFormatter::format($sale->created_at) }}
                    </td>

                    <td style="padding:13px;">
                        {{ $sale->details->count() }} produk
                        @foreach($sale->details as $detail)
                            <div style="margin-bottom:5px;">
                                {{ $detail->product->name ?? 'Produk tidak tersedia' }}
                                <span style="color:#777;">
                                    ({{ \App\Support\InventoryNumberFormatter::format($detail->quantity) }} x)
                                </span>
                            </div>
                        @endforeach
                    </td>

                </tr>
            @empty
                <tr>
                    <td colspan="5" style="padding:30px; text-align:center; color:#888;">
                        Belum ada transaksi penjualan.
                    </td>
                </tr>
            @endforelse
        </tbody>

    </table>
    </div>

    <div style="margin-top:20px;">
        {{ $sales->links() }}
    </div>

</div>

@endsection
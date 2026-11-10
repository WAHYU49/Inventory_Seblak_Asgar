@extends('layouts.app')

@section('content')

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:25px;">
    <div>
        <h2 style="color:#183b2b; margin-bottom:5px;">Data Penjualan</h2>
        <p style="color:#777; font-size:14px;">
            Daftar transaksi penjualan menu Seblak Asgar.
        </p>
    </div>

    <a href="{{ route('sales.create') }}"
       style="background:#183b2b; color:white; padding:12px 18px; border-radius:7px; text-decoration:none; font-size:14px;">
        + Tambah Penjualan
    </a>
</div>

@if(session('success'))
    <div style="background:#dcfce7; color:#166534; padding:13px; border-radius:7px; margin-bottom:20px;">
        {{ session('success') }}
    </div>
@endif

@if ($errors->any())
    <div style="background:#fde8e8; color:#a52828; padding:12px;
        border-radius:6px; margin-bottom:15px;">
        @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

<div style="background:white; padding:20px; border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,0.04); overflow-x:auto;">

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
        <button type="submit">Filter</button>
        <a href="{{ route('sales.index') }}"
            style="background:#eee; color:#333; padding:7px 12px; border-radius:4px; text-decoration:none;">
            Reset
        </a>
    </form>

    <table style="width:100%; border-collapse:collapse; font-size:14px;">

        <thead>
            <tr style="background:#f3f6f2; color:#183b2b;">
                <th style="padding:13px; text-align:left;">No.</th>
                <th style="padding:13px; text-align:left;">Nomor Transaksi</th>
                <th style="padding:13px; text-align:left;">Tanggal</th>
                <th style="padding:13px; text-align:left;">Tanggal Input</th>
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
                        {{ $sale->created_at->format('d-m-Y H:i') }}
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

    <div style="margin-top:20px;">
        {{ $sales->links() }}
    </div>

</div>

@endsection
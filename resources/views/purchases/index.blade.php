@extends('layouts.app')

@section('title', 'Pembelian Bahan Baku - Inventory Seblak Asgar')

@section('content')

<div class="page-header">
    <h1>Pembelian Bahan Baku</h1>
    <p>Kelola transaksi pembelian dan penambahan stok bahan baku.</p>
</div>

<div style="margin-bottom: 20px;">
    <a href="{{ route('purchases.create') }}" class="btn btn-primary">+ Tambah Pembelian</a>
</div>

@if (session('success'))
    <div class="alert alert-success" role="status">
        {{ session('success') }}
    </div>
@endif

<div class="content-card">

    <h3 style="margin-top:0;">Riwayat Pembelian</h3>

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form action="{{ route('purchases.index') }}" method="GET"
        style="display:flex; flex-wrap:wrap; gap:12px; align-items:end; margin-bottom:20px;">
        <div>
            <label for="purchase-start-date">Dari Tanggal</label><br>
            <input id="purchase-start-date" type="date" name="start_date" value="{{ $startDate }}">
        </div>
        <div>
            <label for="purchase-end-date">Sampai Tanggal</label><br>
            <input id="purchase-end-date" type="date" name="end_date" value="{{ $endDate }}">
        </div>
        <button type="submit" class="btn btn-primary">Filter</button>
        <a href="{{ route('purchases.index') }}" class="btn btn-secondary">Reset</a>
    </form>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th class="table-number">No</th>
                    <th>Nomor Pembelian</th>
                    <th>Tanggal</th>
                    <th>Waktu Input (WIB)</th>
                    <th>Jumlah Bahan / Kuantitas</th>
                    <th>Catatan</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($purchases as $index => $purchase)
                    <tr>
                        <td class="table-number">
                            {{ $purchases->firstItem() + $index }}
                        </td>

                        <td>
                            {{ $purchase->purchase_number }}
                        </td>

                        <td>
                            {{ $purchase->purchase_date->format('d-m-Y') }}
                        </td>

                        <td>
                            {{ \App\Support\WibDateTimeFormatter::format($purchase->created_at) }}
                        </td>

                        <td>
                            {{ $purchase->details->count() }} bahan
                            @foreach ($purchase->details as $detail)
                                <div>
                                    {{ $detail->ingredient->name ?? 'Bahan tidak ditemukan' }}:
                                    {{ \App\Support\InventoryNumberFormatter::format($detail->quantity) }}
                                    {{ $detail->ingredient->unit ?? '' }}
                                </div>
                            @endforeach
                        </td>

                        <td>
                            {{ $purchase->notes ?: '-' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            Belum ada transaksi pembelian.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $purchases->links() }}

</div>

@endsection
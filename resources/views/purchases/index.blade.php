@extends('layouts.app')

@section('title', 'Pembelian Bahan Baku - Inventory Seblak Asgar')

@section('content')

<div class="page-header">
    <h1>Pembelian Bahan Baku</h1>
    <p>Kelola transaksi pembelian dan penambahan stok bahan baku.</p>
</div>

<div style="margin-bottom: 20px;">
    <a href="{{ route('purchases.create') }}"
       style="background:#183b2b; color:white; padding:10px 16px;
       text-decoration:none; border-radius:6px;">
        + Tambah Pembelian
    </a>
</div>

@if (session('success'))
    <div style="background:#dff3e5; color:#216b3a; padding:12px;
        border-radius:6px; margin-bottom:15px;">
        {{ session('success') }}
    </div>
@endif

<div style="background:white; padding:20px; border-radius:10px;
    box-shadow:0 2px 8px rgba(0,0,0,0.05);">

    <h3 style="margin-top:0;">Riwayat Pembelian</h3>

    <div style="overflow-x:auto;">
        <table style="width:100%; border-collapse:collapse;">
            <thead>
                <tr style="background:#f4f6f5;">
                    <th style="padding:12px; text-align:left;">No</th>
                    <th style="padding:12px; text-align:left;">Nomor Pembelian</th>
                    <th style="padding:12px; text-align:left;">Tanggal</th>
                    <th style="padding:12px; text-align:left;">Tanggal Input</th>
                    <th style="padding:12px; text-align:left;">Jumlah Item</th>
                    <th style="padding:12px; text-align:left;">Total Pembelian</th>
                    <th style="padding:12px; text-align:left;">Catatan</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($purchases as $index => $purchase)
                    <tr style="border-bottom:1px solid #eee;">
                        <td style="padding:12px;">
                            {{ $purchases->firstItem() + $index }}
                        </td>

                        <td style="padding:12px;">
                            {{ $purchase->purchase_number }}
                        </td>

                        <td style="padding:12px;">
                            {{ $purchase->purchase_date->format('d-m-Y') }}
                        </td>

                        <td style="padding:12px;">
                            {{ $purchase->created_at->format('d-m-Y H:i') }}
                        </td>

                        <td style="padding:12px;">
                            {{ $purchase->details->count() }} item
                        </td>

                        <td style="padding:12px;">
                            Rp {{ number_format($purchase->total_amount, 0, ',', '.') }}
                        </td>

                        <td style="padding:12px;">
                            {{ $purchase->notes ?: '-' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="padding:20px; text-align:center;">
                            Belum ada transaksi pembelian.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top:20px;">
        {{ $purchases->links() }}
    </div>

</div>

@endsection
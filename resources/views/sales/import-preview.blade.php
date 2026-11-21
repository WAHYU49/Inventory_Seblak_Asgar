@extends('layouts.app')

@section('title', 'Preview Import Penjualan - Inventory Seblak Asgar')

@section('content')
<div class="page-header">
    <h1>Preview Import Penjualan</h1>
    <p>Periksa data dan kesalahan sebelum mengonfirmasi transaksi.</p>
</div>

<p><a href="{{ route('sales.import') }}" class="btn btn-secondary">← Pilih file lain</a></p>

@if ($errors->any())
    <div class="alert alert-danger" role="alert">
        @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

<div class="content-card">
    <p><strong>Tanggal Penjualan:</strong> {{ \Illuminate\Support\Carbon::parse($saleDate)->format('d-m-Y') }}</p>
    <p><strong>Baris kosong dilewati:</strong> {{ $skipped }}</p>
    <p>Preview belum mengubah stok. Stok hanya diproses setelah konfirmasi.</p>
</div>

@if ($rowErrors !== [])
    <div class="alert alert-warning" role="alert">
        <h2>Kesalahan per baris</h2>
        <ul>
            @foreach ($rowErrors as $rowError)
                <li>Baris {{ $rowError['row'] }}: {{ $rowError['message'] }}</li>
            @endforeach
        </ul>
        <p>Perbaiki file dan unggah kembali. Import tidak dapat dikonfirmasi selama ada kesalahan.</p>
    </div>
@endif

<div class="content-card">
    <h2>Hasil penggabungan</h2>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Produk</th>
                    <th>Baris sumber</th>
                    <th>Jumlah awal</th>
                    <th>Total hasil penggabungan</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td>{{ $row['product_name'] }}</td>
                        <td>
                            @foreach ($row['source_rows'] as $sourceRow)
                                @if (! $loop->first)<br>@endif
                                Baris {{ $sourceRow['row'] }}
                            @endforeach
                        </td>
                        <td>
                            @foreach ($row['source_rows'] as $sourceRow)
                                @if (! $loop->first) + @endif
                                {{ \App\Support\InventoryNumberFormatter::format($sourceRow['quantity']) }}
                            @endforeach
                        </td>
                        <td>{{ \App\Support\InventoryNumberFormatter::format($row['total_quantity']) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">Tidak ada baris valid untuk ditampilkan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if ($completed)
    <div class="alert alert-success" role="status">
        Import ini sudah diproses. Mengirim ulang konfirmasi tidak akan membuat transaksi tambahan.
    </div>
@elseif ($rowErrors === [] && $rows !== [])
    <div class="content-card">
        <form action="{{ route('sales.import.commit') }}" method="POST">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <button type="submit" class="btn btn-primary"
                onclick="return confirm('Konfirmasi import penjualan dan pengurangan stok berdasarkan resep?')">
                Konfirmasi Import
            </button>
        </form>
    </div>
@endif
@endsection

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
    <ul>
        <li>Total baris Excel: {{ $summary['total_rows'] }}</li>
        <li>Baris valid: {{ $summary['valid_rows'] }}</li>
        <li>Baris Terjual = 0 dilewati: {{ $summary['zero_skipped'] }}</li>
        <li>Produk tidak ditemukan: {{ $summary['not_found'] }}</li>
        <li>Jumlah tidak valid: {{ $summary['invalid_quantities'] }}</li>
        <li>Total unit penjualan valid: {{ \App\Support\InventoryNumberFormatter::format($summary['total_units']) }}</li>
        <li>Baris kosong dilewati: {{ $emptyRowsSkipped }}</li>
    </ul>
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
    <h2>Detail baris Excel</h2>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th class="table-number">Baris</th>
                    <th>Nama Produk dari Excel</th>
                    <th>Nama Produk di Database</th>
                    <th>Terjual</th>
                    <th>Status Pencocokan</th>
                    <th>Status Validasi</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($previewRows as $row)
                    <tr>
                        <td class="table-number">{{ $row['row'] }}</td>
                        <td>{{ $row['product_excel'] !== '' ? $row['product_excel'] : '—' }}</td>
                        <td>{{ $row['product_matched'] }}</td>
                        <td>
                            {{ $row['quantity'] === null
                                ? '—'
                                : \App\Support\InventoryNumberFormatter::format($row['quantity']) }}
                        </td>
                        <td>{{ $row['match_status'] }}</td>
                        <td>{{ $row['errors'] !== [] ? 'Bermasalah' : ($row['zero_skipped'] ? 'Dilewati (0)' : 'Valid') }}</td>
                        <td>{{ $row['errors'] !== [] ? implode(' ', $row['errors']) : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7">Tidak ada baris untuk ditampilkan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="content-card">
    <h2>Hasil penggabungan produk yang sama</h2>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Produk</th>
                    <th>Baris sumber</th>
                    <th>Jumlah per baris</th>
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
                        <td colspan="4">Tidak ada penjualan positif yang valid untuk diproses.</td>
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

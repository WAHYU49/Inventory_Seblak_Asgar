@extends('layouts.app')

@section('title', 'Preview Import Bahan Baku - Inventory Seblak Asgar')

@section('content')
<div class="page-header">
    <h1>Preview Import Bahan Baku</h1>
    <p>Periksa ringkasan dan kesalahan sebelum menyimpan data master.</p>
</div>

<p><a href="{{ route('ingredients.import') }}" class="btn btn-secondary">← Pilih file lain</a></p>

<ul>
    <li>Data valid baru: {{ $newCount }}</li>
    <li>Data valid diperbarui: {{ $updatedCount }}</li>
    <li>Baris kosong dilewati: {{ $skipped }}</li>
    <li>Baris gagal: {{ count($rowErrors) }}</li>
</ul>

@if ($rowErrors !== [])
    <div class="alert alert-warning" role="alert">
    <h2>Kesalahan per baris</h2>
    <ul>
        @foreach ($rowErrors as $rowError)
            <li>Baris {{ $rowError['row'] }}: {{ $rowError['message'] }}</li>
        @endforeach
    </ul>
    </div>
@endif

<div class="content-card">
    <h2>Data valid (maksimal 20 baris pertama)</h2>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th class="table-number">Baris Excel</th>
                    <th>SKU ID</th>
                    <th>Nama Barang</th>
                    <th>Kategori</th>
                    <th>Satuan</th>
                    <th class="table-number">Jumlah / Isi</th>
                    <th>Supplier</th>
                    <th>Alokasi</th>
                </tr>
            </thead>
            <tbody>
                @forelse (array_slice($rows, 0, 20) as $row)
                    <tr>
                        <td class="table-number">{{ $row['row_number'] }}</td>
                        <td>{{ $row['code'] }}</td>
                        <td>{{ $row['name'] }}</td>
                        <td>{{ $row['category'] ?? '—' }}</td>
                        <td>{{ $row['unit'] }}</td>
                        <td class="table-number">
                            {{ $row['package_quantity'] === null
                                ? '—'
                                : \App\Support\InventoryNumberFormatter::format($row['package_quantity']) }}
                        </td>
                        <td>{{ $row['supplier'] ?? '—' }}</td>
                        <td>{{ $row['allocation'] ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">Tidak ada baris valid untuk disimpan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if ($rows !== [])
    <div class="content-card">
        <form action="{{ route('ingredients.import.commit') }}" method="POST">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <button type="submit" class="btn btn-primary" onclick="return confirm('Simpan data master yang valid? Stok tidak akan diubah.')">
                Simpan Data Valid
            </button>
        </form>
    </div>
@endif
@endsection

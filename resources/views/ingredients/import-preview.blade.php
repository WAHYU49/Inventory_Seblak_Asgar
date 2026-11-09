@extends('layouts.app')

@section('title', 'Preview Import Bahan Baku - Inventory Seblak Asgar')

@section('content')
<div class="page-header">
    <h1>Preview Import Bahan Baku</h1>
    <p>Periksa ringkasan dan kesalahan sebelum menyimpan data master.</p>
</div>

<p><a href="{{ route('ingredients.import') }}">← Pilih file lain</a></p>

<ul>
    <li>Data valid baru: {{ $newCount }}</li>
    <li>Data valid diperbarui: {{ $updatedCount }}</li>
    <li>Baris kosong dilewati: {{ $skipped }}</li>
    <li>Baris gagal: {{ count($rowErrors) }}</li>
</ul>

@if ($rowErrors !== [])
    <h2>Kesalahan per baris</h2>
    <ul>
        @foreach ($rowErrors as $rowError)
            <li>Baris {{ $rowError['row'] }}: {{ $rowError['message'] }}</li>
        @endforeach
    </ul>
@endif

<h2>Data valid (maksimal 20 baris pertama)</h2>
<table border="1" cellpadding="8" cellspacing="0" width="100%">
    <thead>
        <tr>
            <th>Baris Excel</th>
            <th>SKU ID</th>
            <th>Nama Barang</th>
            <th>Kategori</th>
            <th>Satuan</th>
            <th>Jumlah / Isi</th>
            <th>Supplier</th>
            <th>Alokasi</th>
        </tr>
    </thead>
    <tbody>
        @forelse (array_slice($rows, 0, 20) as $row)
            <tr>
                <td>{{ $row['row_number'] }}</td>
                <td>{{ $row['code'] }}</td>
                <td>{{ $row['name'] }}</td>
                <td>{{ $row['category'] ?? '—' }}</td>
                <td>{{ $row['unit'] }}</td>
                <td>{{ $row['package_quantity'] ?? '—' }}</td>
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

@if ($rows !== [])
    <form action="{{ route('ingredients.import.commit') }}" method="POST" style="margin-top: 20px;">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <button type="submit" onclick="return confirm('Simpan data master yang valid? Stok tidak akan diubah.')">
            Simpan Data Valid
        </button>
    </form>
@endif
@endsection

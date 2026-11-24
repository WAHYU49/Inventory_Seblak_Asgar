@extends('layouts.app')

@section('title', 'Preview Import Produk - Inventory Seblak Asgar')

@section('content')
<div class="page-header">
    <h1>Preview Import Produk</h1>
    <p>Periksa nama produk, kategori dari Excel, dan validasi sebelum mengonfirmasi.</p>
</div>

<p><a href="{{ route('products.import') }}" class="btn btn-secondary">← Pilih file lain</a></p>

<div class="content-card">
    <ul>
        <li>Total data: {{ $counts['total'] }}</li>
        <li>Produk valid yang akan diimport: {{ $counts['valid'] }}</li>
        <li>Produk duplikat dilewati: {{ $counts['duplicates'] }}</li>
        <li>Data bermasalah: {{ $counts['problematic'] }}</li>
        <li>Data yang akan diimport: {{ $counts['to_import'] }}</li>
        <li>Baris kosong dilewati: {{ $counts['skipped'] }}</li>
    </ul>
</div>

@if ($errors !== [])
    <div class="alert alert-warning" role="alert">
        <p>Perbaiki semua data bermasalah sebelum mengonfirmasi. Produk duplikat akan dilewati otomatis. Preview belum mengubah data.</p>
    </div>
@endif

<div class="content-card">
    <h2>Kategori dari Excel</h2>
    <ul>
        @forelse ($categories as $category)
            <li>{{ $category['name'] }} — {{ $category['status'] }}</li>
        @empty
            <li>Tidak ada kategori yang diisi.</li>
        @endforelse
    </ul>
</div>

<div class="content-card">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th class="table-number">Baris</th>
                    <th>Nama Produk</th>
                    <th>Kategori Excel</th>
                    <th>Status Kategori</th>
                    <th>Status</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td class="table-number">{{ $row['row_number'] }}</td>
                        <td>{{ $row['name'] !== '' ? $row['name'] : '—' }}</td>
                        <td>{{ $row['category_name'] !== '' ? $row['category_name'] : '—' }}</td>
                        <td>{{ $row['category_status'] ?? 'Tidak ada kategori' }}</td>
                        <td>{{ $row['status'] }}</td>
                        <td>{{ $row['message'] !== '' ? $row['message'] : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6">Tidak ada data produk.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if ($completed)
    <div class="alert alert-success" role="status">Import ini sudah selesai diproses.</div>
@elseif ($counts['problematic'] === 0 && ($counts['to_import'] > 0 || $counts['duplicates'] > 0))
    <div class="content-card">
        <form action="{{ route('products.import.commit') }}" method="POST">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <button type="submit" class="btn btn-primary"
                onclick="return confirm('Import produk valid dan lewati duplikat?')">
                Konfirmasi Import
            </button>
        </form>
    </div>
@endif
@endsection

@extends('layouts.app')

@section('title', 'Preview Import Produk - Inventory Seblak Asgar')

@section('content')
<div class="page-header">
    <h1>Preview Import Produk dan Resep Menu</h1>
    <p>Periksa produk, resep, bahan baku, dan satuan sebelum mengonfirmasi. Preview tidak mengubah database atau stok.</p>
</div>

<p><a href="{{ route('products.import') }}" class="btn btn-secondary">← Pilih file lain</a></p>

<div class="content-card">
    <ul>
        <li>Total data: {{ $counts['total'] }}</li>
        <li>Produk valid yang akan diimport: {{ $counts['valid'] }}</li>
        <li>Jumlah produk baru: {{ $counts['valid'] }}</li>
        <li>Produk duplikat dilewati: {{ $counts['duplicates'] }}</li>
        <li>Jumlah resep yang akan dibuat: {{ $recipe_counts['to_create'] }}</li>
        <li>Jumlah detail bahan baku: {{ $recipe_counts['details_to_create'] }}</li>
        <li>Bahan baku tidak ditemukan: {{ $recipe_counts['missing_ingredients'] }}</li>
        <li>Produk resep tidak ditemukan/tidak sesuai: {{ $recipe_counts['product_errors'] }}</li>
        <li>Resep yang sudah tersedia (dilewati): {{ $recipe_counts['existing'] }}</li>
        <li>Kesalahan jumlah dan satuan: {{ $recipe_counts['quantity_unit_errors'] }}</li>
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

<div class="content-card">
    <h2>Resep Menu dari Excel</h2>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th class="table-number">Baris</th>
                    <th>Produk</th>
                    <th>Bahan Baku</th>
                    <th>Jumlah</th>
                    <th>Satuan</th>
                    <th>Status</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($recipe_rows as $row)
                    <tr>
                        <td class="table-number">{{ $row['row_number'] }}</td>
                        <td>{{ $row['product_name'] !== '' ? $row['product_name'] : '—' }}</td>
                        <td>{{ $row['ingredient_name'] !== '' ? $row['ingredient_name'] : '—' }}</td>
                        <td>{{ $row['quantity'] !== '' ? $row['quantity'] : '—' }}</td>
                        <td>{{ $row['unit'] !== '' ? $row['unit'] : '—' }}</td>
                        <td>
                            @if ($row['is_existing_recipe'])
                                Resep tersedia — dilewati
                            @elseif ($row['errors'] !== [])
                                Bermasalah
                            @else
                                Valid
                            @endif
                        </td>
                        <td>{{ $row['message'] !== '' ? $row['message'] : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7">Tidak ada detail resep pada file.</td></tr>
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

@extends('layouts.app')

@section('title', 'Preview Import Produk - Inventory Seblak Asgar')

@section('content')
<div class="page-header">
    <h1>Preview Import Produk</h1>
    <p>Periksa nama produk, kategori, dan validasi sebelum mengonfirmasi.</p>
</div>

<p><a href="{{ route('products.import') }}" class="btn btn-secondary">← Pilih file lain</a></p>

<div class="content-card">
    <ul>
        <li>Total data: {{ $counts['total'] }}</li>
        <li>Data valid: {{ $counts['valid'] }}</li>
        <li>Data duplikat: {{ $counts['duplicates'] }}</li>
        <li>Data bermasalah: {{ $counts['problematic'] }}</li>
        <li>Data yang akan diimport: {{ $counts['to_import'] }}</li>
        <li>Baris kosong dilewati: {{ $counts['skipped'] }}</li>
    </ul>
</div>

@if ($errors !== [])
    <div class="alert alert-warning" role="alert">
        <p>Perbaiki semua data duplikat dan bermasalah sebelum mengonfirmasi. Import belum mengubah data.</p>
    </div>
@endif

@if ($mappingErrors !== [])
    <div class="alert alert-danger" role="alert">
        @foreach ($mappingErrors as $mappingError)
            <p>{{ $mappingError }}</p>
        @endforeach
    </div>
@endif

@if ($mappingCatalog !== [])
    <div class="content-card">
        <h2>Pemetaan kategori Excel</h2>
        <p>Pilih kategori database untuk setiap kategori Excel yang belum cocok. Kategori baru tidak akan dibuat.</p>
        <form action="{{ route('products.import.mapping') }}" method="POST">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            @foreach ($mappingCatalog as $key => $category)
                <div class="form-group">
                    <label for="category-map-{{ $key }}">
                        Kategori Excel: {{ $category['source_name'] }}
                    </label>
                    <select id="category-map-{{ $key }}" name="mappings[{{ $key }}]" required>
                        <option value="">-- Pilih kategori database --</option>
                        @foreach ($category['options'] as $option)
                            <option value="{{ $option['id'] }}"
                                {{ (string) ($mappings[$key]['id'] ?? '') === (string) $option['id'] ? 'selected' : '' }}>
                                {{ $option['name'] }}
                            </option>
                        @endforeach
                    </select>
                    @if ($category['options'] === [])
                        <small class="field-error">Belum ada kategori tersedia di database.</small>
                    @endif
                </div>
            @endforeach
            <button type="submit" class="btn btn-secondary"
                {{ collect($mappingCatalog)->every(fn ($category) => $category['options'] !== []) ? '' : 'disabled' }}>
                Simpan Pemetaan Kategori
            </button>
        </form>
    </div>
@endif

<div class="content-card">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th class="table-number">Baris</th>
                    <th>Nama Produk</th>
                    <th>Kategori Excel</th>
                    <th>Hasil Pemetaan</th>
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
                        <td>{{ $row['mapped_category'] ?? 'Belum dipetakan' }}</td>
                        <td>{{ $row['status'] }}</td>
                        <td>{{ $row['errors'] !== [] ? implode(' ', $row['errors']) : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5">Tidak ada data produk.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if ($completed)
    <div class="alert alert-success" role="status">Import ini sudah selesai diproses.</div>
@elseif ($errors === [] && $counts['to_import'] > 0)
    <div class="content-card">
        <form action="{{ route('products.import.commit') }}" method="POST">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <button type="submit" class="btn btn-primary"
                onclick="return confirm('Tambahkan seluruh produk valid yang ditampilkan?')">
                Konfirmasi Import
            </button>
        </form>
    </div>
@endif
@endsection

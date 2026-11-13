
@extends('layouts.app')

@section('title', 'Bahan Baku - Inventory Seblak Asgar')

@section('content')

<div class="page-header">
    <h1>Data Bahan Baku</h1>
    <p>Kelola persediaan bahan baku dan bumbu untuk kebutuhan operasional Seblak Asgar.</p>
</div>

@if (session('success'))
    <div class="alert alert-success" role="status">{{ session('success') }}</div>
@endif

@if (session('error'))
    <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
@endif

@if ($errors->any())
    <div class="alert alert-danger" role="alert">
        @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

<p>
    <a href="{{ route('ingredients.create') }}" class="btn btn-primary">+ Tambah Bahan Baku</a>
    <a href="{{ route('ingredients.import') }}" class="btn btn-secondary">Import Master Bahan Baku</a>
    <a href="{{ route('ingredients.export') }}" class="btn btn-secondary">Export Excel</a>
</p>

<form action="{{ route('ingredients.index') }}" method="GET"
    style="display:flex; flex-wrap:wrap; gap:10px; align-items:end; margin-bottom:20px;">
    <div>
        <label for="stock-status">Status Stok</label><br>
        <select id="stock-status" name="stock_status">
            <option value="">Semua Status</option>
            <option value="empty" {{ $stockStatus === 'empty' ? 'selected' : '' }}>Stok Habis</option>
            <option value="low" {{ $stockStatus === 'low' ? 'selected' : '' }}>Stok Menipis</option>
            <option value="safe" {{ $stockStatus === 'safe' ? 'selected' : '' }}>Stok Aman</option>
        </select>
    </div>
    <button type="submit" class="btn btn-primary">Filter</button>
    <a href="{{ route('ingredients.index') }}" class="btn btn-secondary">Reset</a>
</form>

<div class="table-responsive">
<table border="1" cellpadding="10" cellspacing="0" width="100%">
    <thead>
        <tr>
            <th>No.</th>
            <th>Kode</th>
            <th>Nama Bahan</th>
            <th>Kategori</th>
            <th>Satuan</th>
            <th>Stok Saat Ini</th>
            <th>Stok Minimum</th>
            <th>Status Stok</th>
            <th>Status Data</th>
            <th>Waktu Input (WIB)</th>
            <th>Aksi</th>
        </tr>
    </thead>

    <tbody>
        @forelse ($ingredients as $ingredient)
            <tr>
                <td>{{ $loop->iteration + ($ingredients->currentPage() - 1) * $ingredients->perPage() }}</td>

                <td>{{ $ingredient->code }}</td>

                <td>{{ $ingredient->name }}</td>

                <td>{{ $ingredient->category ?? '-' }}</td>

                <td>{{ $ingredient->unit }}</td>

                <td>{{ \App\Support\InventoryNumberFormatter::format($ingredient->current_stock) }}</td>

                <td>{{ \App\Support\InventoryNumberFormatter::format($ingredient->minimum_stock) }}</td>

                <td>
                    @if ($ingredient->current_stock <= 0)
                        <span class="badge badge-danger">Stok Habis</span>
                    @elseif ($ingredient->current_stock <= $ingredient->minimum_stock)
                        <span class="badge badge-warning">Stok Menipis</span>
                    @else
                        <span class="badge badge-success">Stok Aman</span>
                    @endif
                </td>

                <td>
                    <span class="badge {{ $ingredient->is_active ? 'badge-success' : 'badge-secondary' }}">
                        {{ $ingredient->is_active ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </td>

                <td>{{ \App\Support\WibDateTimeFormatter::format($ingredient->created_at) }}</td>

                <td>
                    <a href="{{ route('ingredients.edit', $ingredient->id) }}" class="btn btn-secondary">
                        Edit
                    </a>

                    @if ($ingredient->is_active)
                        <form action="{{ route('ingredients.destroy', $ingredient->id) }}"
                              method="POST"
                              style="display: inline;"
                              onsubmit="return confirm('Yakin ingin menonaktifkan bahan baku ini?')">
                            @csrf
                            @method('DELETE')

                            <button type="submit" class="btn btn-danger">Nonaktifkan</button>
                        </form>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="11">Belum ada data bahan baku.</td>
            </tr>
        @endforelse
    </tbody>
</table>
</div>

<div style="margin-top: 20px;">
    {{ $ingredients->links() }}
</div>

@endsection

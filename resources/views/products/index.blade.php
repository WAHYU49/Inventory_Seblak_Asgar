
@extends('layouts.app')

@section('title', 'Produk Menu - Inventory Seblak Asgar')

@section('content')

<div class="page-header">
    <h1>Produk Menu</h1>
    <p>Kelola daftar menu makanan Seblak Asgar.</p>
</div>

@if (session('success'))
    <div class="alert alert-success" role="status">{{ session('success') }}</div>
@endif

@if (session('error'))
    <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
@endif

<p>
    <a href="{{ route('products.create') }}" class="btn btn-primary">+ Tambah Produk</a>
</p>

<div class="content-card">
    <form action="{{ route('products.index') }}" method="GET"
        style="display:flex; flex-wrap:wrap; gap:12px; align-items:end;">
        <div class="form-group" style="flex:2 1 240px;">
            <label for="product-search">Cari Nama atau Kode Produk</label>
            <input id="product-search" type="search" name="search" value="{{ $search }}"
                placeholder="Masukkan nama atau kode produk">
        </div>

        <div class="form-group" style="flex:1 1 180px;">
            <label for="product-category">Kategori</label>
            <select id="product-category" name="category_id">
                <option value="">Semua Kategori</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}"
                        {{ (string) $categoryId === (string) $category->id ? 'selected' : '' }}>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="form-group" style="flex:1 1 160px;">
            <label for="product-status">Status</label>
            <select id="product-status" name="status">
                <option value="">Semua Status</option>
                <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Aktif</option>
                <option value="inactive" {{ $status === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Filter</button>
        <a href="{{ route('products.index') }}" class="btn btn-secondary">Reset Filter</a>
    </form>
</div>

<div class="table-responsive">
<table border="1" cellpadding="10" cellspacing="0" width="100%">
    <thead>
        <tr>
            <th>No.</th>
            <th>Kode Produk</th>
            <th>Nama Produk</th>
            <th>Kategori</th>
            <th>Status</th>
            <th>Waktu Input (WIB)</th>
            <th>Aksi</th>
        </tr>
    </thead>

    <tbody>
        @forelse ($products as $product)
            <tr>
                <td>{{ $loop->iteration + ($products->currentPage() - 1) * $products->perPage() }}</td>
                <td>{{ $product->code }}</td>
                <td>{{ $product->name }}</td>
                <td>{{ $product->category->name ?? '-' }}</td>
                <td>
                    <span class="badge {{ $product->is_active ? 'badge-success' : 'badge-secondary' }}">
                        {{ $product->is_active ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </td>
                <td>{{ \App\Support\WibDateTimeFormatter::format($product->created_at) }}</td>
                <td>
                    <a href="{{ route('products.edit', $product->id) }}" class="btn btn-secondary">Edit</a>

                    <form action="{{ route('products.destroy', $product->id) }}"
                          method="POST"
                          style="display:inline;"
                          onsubmit="return confirm('Yakin ingin menghapus produk ini?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Hapus</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7">Belum ada produk menu.</td>
            </tr>
        @endforelse
    </tbody>
</table>
</div>

<div style="margin-top: 20px;">
    {{ $products->links() }}
</div>

@endsection

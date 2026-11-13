
@extends('layouts.app')

@section('title', 'Resep Menu - Inventory Seblak Asgar')

@section('content')

<div class="page-header">
    <h1>Resep Menu</h1>
    <p>Kelola komposisi bahan baku untuk setiap menu makanan Seblak Asgar.</p>
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
    <a href="{{ route('recipes.create') }}" class="btn btn-primary">+ Tambah Resep</a>
</p>

<div class="content-card">
    <form action="{{ route('recipes.index') }}" method="GET"
        style="display:flex; flex-wrap:wrap; gap:12px; align-items:end;">
        <div class="form-group" style="flex:2 1 240px;">
            <label for="recipe-search">Cari Nama Produk</label>
            <input id="recipe-search" type="search" name="search" value="{{ $search }}"
                placeholder="Masukkan nama produk">
        </div>

        <div class="form-group" style="flex:1 1 180px;">
            <label for="recipe-category">Kategori Produk</label>
            <select id="recipe-category" name="category_id">
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
            <label for="recipe-status">Status Resep</label>
            <select id="recipe-status" name="status">
                <option value="">Semua Status</option>
                <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Aktif</option>
                <option value="inactive" {{ $status === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Filter</button>
        <a href="{{ route('recipes.index') }}" class="btn btn-secondary">Reset Filter</a>
    </form>
</div>

<div class="table-responsive">
<table border="1" cellpadding="10" cellspacing="0" width="100%">
    <thead>
        <tr>
            <th>No.</th>
            <th>Nama Menu</th>
            <th>Nama Resep</th>
            <th>Komposisi Bahan</th>
            <th>Status</th>
            <th>Waktu Input (WIB)</th>
            <th>Aksi</th>
        </tr>
    </thead>

    <tbody>
        @forelse ($recipes as $recipe)
            <tr>
                <td>
                    {{ $loop->iteration + ($recipes->currentPage() - 1) * $recipes->perPage() }}
                </td>

                <td>{{ $recipe->product->name ?? '-' }}</td>

                <td>{{ $recipe->name }}</td>

                <td>
                    @foreach ($recipe->details as $detail)
                        {{ $detail->ingredient->name ?? 'Bahan tidak ditemukan' }}
                        ({{ \App\Support\InventoryNumberFormatter::format($detail->quantity) }} {{ $detail->ingredient->unit ?? '' }})
                        <br>
                    @endforeach
                </td>

                <td>
                    <span class="badge {{ $recipe->is_active ? 'badge-success' : 'badge-secondary' }}">
                        {{ $recipe->is_active ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </td>

                <td>{{ \App\Support\WibDateTimeFormatter::format($recipe->created_at) }}</td>

                <td>
                    <a href="{{ route('recipes.edit', $recipe->id) }}" class="btn btn-secondary">Edit</a>

                    <form action="{{ route('recipes.destroy', $recipe->id) }}"
                          method="POST"
                          style="display: inline;"
                          onsubmit="return confirm('Yakin ingin menghapus resep ini?')">
                        @csrf
                        @method('DELETE')

                        <button type="submit" class="btn btn-danger">Hapus</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7">Belum ada data resep.</td>
            </tr>
        @endforelse
    </tbody>
</table>
</div>

<div style="margin-top: 20px;">
    {{ $recipes->links() }}
</div>

@endsection

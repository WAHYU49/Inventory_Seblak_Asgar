
@extends('layouts.app')

@section('title', 'Kategori Menu - Inventory Seblak Asgar')

@section('content')

<div class="page-header">
    <h1>Kategori Menu</h1>
    <p>Kelola kategori menu yang tersedia di Seblak Asgar.</p>
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
    <a href="{{ route('categories.create') }}" class="btn btn-primary">+ Tambah Kategori</a>
</p>

<div class="content-card">
    <form action="{{ route('categories.index') }}" method="GET"
        style="display:flex; flex-wrap:wrap; gap:12px; align-items:end;">
        <div class="form-group" style="flex:1 1 240px;">
            <label for="category-search">Cari Nama Kategori</label>
            <input id="category-search" type="search" name="search" value="{{ $search }}"
                placeholder="Masukkan nama kategori">
        </div>
        <button type="submit" class="btn btn-primary">Cari</button>
        <a href="{{ route('categories.index') }}" class="btn btn-secondary">Reset Filter</a>
    </form>
</div>

<div class="table-responsive">
<table border="1" cellpadding="10" cellspacing="0" width="100%">
    <thead>
        <tr>
            <th>No.</th>
            <th>Nama Kategori</th>
            <th>Deskripsi</th>
            <th>Status</th>
            <th>Waktu Input (WIB)</th>
            <th>Aksi</th>
        </tr>
    </thead>

    <tbody>
        @forelse ($categories as $category)
            <tr>
                <td>{{ $loop->iteration + ($categories->currentPage() - 1) * $categories->perPage() }}</td>
                <td>{{ $category->name }}</td>
                <td>{{ $category->description ?? '-' }}</td>
                <td>
                    <span class="badge {{ $category->is_active ? 'badge-success' : 'badge-secondary' }}">
                        {{ $category->is_active ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </td>
                <td>{{ \App\Support\WibDateTimeFormatter::format($category->created_at) }}</td>
                <td>
                    <a href="{{ route('categories.edit', $category->id) }}" class="btn btn-secondary">Edit</a>

                    <form action="{{ route('categories.destroy', $category->id) }}"
                          method="POST"
                          style="display:inline;"
                          onsubmit="return confirm('Yakin ingin menghapus kategori ini?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Hapus</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6">Belum ada kategori.</td>
            </tr>
        @endforelse
    </tbody>
</table>
</div>

<div style="margin-top: 20px;">
    {{ $categories->links() }}
</div>

@endsection

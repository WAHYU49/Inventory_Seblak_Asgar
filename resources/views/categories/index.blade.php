
@extends('layouts.app')

@section('title', 'Kategori Menu - Inventory Seblak Asgar')

@section('content')

<div class="page-header">
    <h1>Kategori Menu</h1>
    <p>Kelola kategori menu yang tersedia di Seblak Asgar.</p>
</div>

@if (session('success'))
    <p style="color: green;">{{ session('success') }}</p>
@endif

@if (session('error'))
    <p style="color: red;">{{ session('error') }}</p>
@endif

@if ($errors->any())
    <div style="color: red;">
        @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

<p>
    <a href="{{ route('categories.create') }}">+ Tambah Kategori</a>
</p>

<table border="1" cellpadding="10" cellspacing="0" width="100%">
    <thead>
        <tr>
            <th>No.</th>
            <th>Nama Kategori</th>
            <th>Deskripsi</th>
            <th>Status</th>
            <th>Tanggal Input</th>
            <th>Aksi</th>
        </tr>
    </thead>

    <tbody>
        @forelse ($categories as $category)
            <tr>
                <td>{{ $loop->iteration + ($categories->currentPage() - 1) * $categories->perPage() }}</td>
                <td>{{ $category->name }}</td>
                <td>{{ $category->description ?? '-' }}</td>
                <td>{{ $category->is_active ? 'Aktif' : 'Nonaktif' }}</td>
                <td>{{ $category->created_at->format('d-m-Y H:i') }}</td>
                <td>
                    <a href="{{ route('categories.edit', $category->id) }}">Edit</a>

                    <form action="{{ route('categories.destroy', $category->id) }}"
                          method="POST"
                          style="display:inline;"
                          onsubmit="return confirm('Yakin ingin menghapus kategori ini?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit">Hapus</button>
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

<div style="margin-top: 20px;">
    {{ $categories->links() }}
</div>

@endsection

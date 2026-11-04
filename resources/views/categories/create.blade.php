
@extends('layouts.app')

@section('title', 'Tambah Kategori - Inventory Seblak Asgar')

@section('content')

<div class="page-header">
    <h1>Tambah Kategori Menu</h1>
    <p>Tambahkan kategori baru untuk mengelompokkan produk menu.</p>
</div>

<p>
    <a href="{{ route('categories.index') }}">← Kembali ke Kategori</a>
</p>

@if ($errors->any())
    <div style="color: red;">
        @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

<form action="{{ route('categories.store') }}" method="POST">
    @csrf

    <div style="margin-bottom: 15px;">
        <label>Nama Kategori</label><br>
        <input type="text"
               name="name"
               value="{{ old('name') }}"
               placeholder="Contoh: Seblak"
               required>
    </div>

    <div style="margin-bottom: 15px;">
        <label>Deskripsi</label><br>
        <textarea name="description"
                  rows="4"
                  placeholder="Keterangan kategori">{{ old('description') }}</textarea>
    </div>

    <button type="submit">Simpan Kategori</button>
</form>

@endsection

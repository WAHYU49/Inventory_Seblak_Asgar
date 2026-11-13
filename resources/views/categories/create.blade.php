
@extends('layouts.app')

@section('title', 'Tambah Kategori - Inventory Seblak Asgar')

@section('content')

<div class="page-header">
    <h1>Tambah Kategori Menu</h1>
    <p>Tambahkan kategori baru untuk mengelompokkan produk menu.</p>
</div>
<p><a href="{{ route('categories.index') }}" class="btn btn-secondary">← Kembali ke Kategori</a></p>

@if ($errors->any())
    <div class="alert alert-danger" role="alert">
        <strong>Periksa kembali isian berikut:</strong>
        @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

<div class="content-card">
    <form action="{{ route('categories.store') }}" method="POST">
        @csrf

        <div class="form-group">
            <label for="name">Nama Kategori</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}"
                placeholder="Contoh: Seblak" required>
            @error('name') <small class="text-danger" style="display:block; color:#b91c1c;">{{ $message }}</small> @enderror
        </div>

        <div class="form-group">
            <label for="description">Deskripsi</label>
            <textarea id="description" name="description" rows="4"
                placeholder="Keterangan kategori">{{ old('description') }}</textarea>
            @error('description') <small class="text-danger" style="display:block; color:#b91c1c;">{{ $message }}</small> @enderror
        </div>

        <button type="submit" class="btn btn-primary">Simpan Kategori</button>
        <a href="{{ route('categories.index') }}" class="btn btn-secondary">Batal</a>
    </form>
</div>

@endsection

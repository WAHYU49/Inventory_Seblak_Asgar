
@extends('layouts.app')

@section('title', 'Tambah Produk - Inventory Seblak Asgar')

@section('content')

<div class="page-header">
    <h1>Tambah Produk Menu</h1>
    <p>Tambahkan menu makanan baru ke dalam sistem inventory.</p>
</div>
<p><a href="{{ route('products.index') }}" class="btn btn-secondary">← Kembali ke Produk Menu</a></p>

@if ($errors->any())
    <div class="alert alert-danger" role="alert">
        <strong>Periksa kembali isian berikut:</strong>
        @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

@if ($categories->isEmpty())
    <div class="alert alert-info" role="status">
        <strong>Belum ada kategori menu.</strong>
        <p>Tambahkan kategori terlebih dahulu sebelum membuat produk.</p>
        <a href="{{ route('categories.create') }}" class="btn btn-primary">Tambah Kategori</a>
    </div>
@else
<div class="content-card">
    <form action="{{ route('products.store') }}" method="POST">
        @csrf

        <div class="form-group">
            <label for="category_id">Kategori Menu</label>
            <select id="category_id" name="category_id" required
                @error('category_id') aria-invalid="true" aria-describedby="category-id-error" @enderror>
                <option value="">-- Pilih Kategori --</option>

                @foreach ($categories as $category)
                    <option value="{{ $category->id }}"
                        {{ old('category_id') == $category->id ? 'selected' : '' }}>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
            @error('category_id') <small id="category-id-error" class="field-error">{{ $message }}</small> @enderror
        </div>

        <div class="form-group">
            <label for="code">Kode Produk</label>
            <input id="code" type="text" name="code" value="{{ old('code') }}"
                placeholder="Contoh: MNU001" required
                @error('code') aria-invalid="true" aria-describedby="code-error" @enderror>
            @error('code') <small id="code-error" class="field-error">{{ $message }}</small> @enderror
        </div>

        <div class="form-group">
            <label for="name">Nama Produk</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}"
                placeholder="Contoh: Seblak Original" required
                @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
            @error('name') <small id="name-error" class="field-error">{{ $message }}</small> @enderror
        </div>

        <div class="form-group">
            <label for="description">Deskripsi</label>
            <textarea id="description" name="description" rows="4"
                placeholder="Keterangan produk (opsional)"
                @error('description') aria-invalid="true" aria-describedby="description-error" @enderror>{{ old('description') }}</textarea>
            @error('description') <small id="description-error" class="field-error">{{ $message }}</small> @enderror
        </div>

        <button type="submit" class="btn btn-primary">Simpan Produk</button>
        <a href="{{ route('products.index') }}" class="btn btn-secondary">Batal</a>
    </form>
</div>
@endif

@endsection

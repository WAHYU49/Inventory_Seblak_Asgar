
@extends('layouts.app')

@section('title', 'Edit Produk - Inventory Seblak Asgar')

@section('content')

<div class="page-header">
    <h1>Edit Produk Menu</h1>
    <p>Perbarui informasi produk menu.</p>
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

<div class="content-card">
    <form action="{{ route('products.update', $product->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="form-group">
            <label for="category_id">Kategori Menu</label>
            <select id="category_id" name="category_id" required>
                <option value="">-- Pilih Kategori --</option>

                @foreach ($categories as $category)
                    <option value="{{ $category->id }}"
                        {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
            @error('category_id') <small class="text-danger" style="display:block; color:#b91c1c;">{{ $message }}</small> @enderror
        </div>

        <div class="form-group">
            <label for="code">Kode Produk</label>
            <input id="code" type="text" name="code"
                value="{{ old('code', $product->code) }}" required>
            @error('code') <small class="text-danger" style="display:block; color:#b91c1c;">{{ $message }}</small> @enderror
        </div>

        <div class="form-group">
            <label for="name">Nama Produk</label>
            <input id="name" type="text" name="name"
                value="{{ old('name', $product->name) }}" required>
            @error('name') <small class="text-danger" style="display:block; color:#b91c1c;">{{ $message }}</small> @enderror
        </div>

        <div class="form-group">
            <label for="description">Deskripsi</label>
            <textarea id="description" name="description" rows="4"
                placeholder="Keterangan produk (opsional)">{{ old('description', $product->description) }}</textarea>
            @error('description') <small class="text-danger" style="display:block; color:#b91c1c;">{{ $message }}</small> @enderror
        </div>

        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
        <a href="{{ route('products.index') }}" class="btn btn-secondary">Batal</a>
    </form>
</div>

@endsection

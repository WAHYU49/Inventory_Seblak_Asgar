
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

<div class="content-card">
    <form action="{{ route('products.store') }}" method="POST">
        @csrf

        <div class="form-group">
            <label for="category_id">Kategori Menu</label>
            <select id="category_id" name="category_id" required>
                <option value="">-- Pilih Kategori --</option>

                @foreach ($categories as $category)
                    <option value="{{ $category->id }}"
                        {{ old('category_id') == $category->id ? 'selected' : '' }}>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
            @error('category_id') <small class="text-danger" style="display:block; color:#b91c1c;">{{ $message }}</small> @enderror
        </div>

        <div class="form-group">
            <label for="code">Kode Produk</label>
            <input id="code" type="text" name="code" value="{{ old('code') }}"
                placeholder="Contoh: MNU001" required>
            @error('code') <small class="text-danger" style="display:block; color:#b91c1c;">{{ $message }}</small> @enderror
        </div>

        <div class="form-group">
            <label for="name">Nama Produk</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}"
                placeholder="Contoh: Seblak Original" required>
            @error('name') <small class="text-danger" style="display:block; color:#b91c1c;">{{ $message }}</small> @enderror
        </div>

        <div class="form-group">
            <label for="description">Deskripsi</label>
            <textarea id="description" name="description" rows="4"
                placeholder="Keterangan produk (opsional)">{{ old('description') }}</textarea>
            @error('description') <small class="text-danger" style="display:block; color:#b91c1c;">{{ $message }}</small> @enderror
        </div>

        <button type="submit" class="btn btn-primary">Simpan Produk</button>
        <a href="{{ route('products.index') }}" class="btn btn-secondary">Batal</a>
    </form>
</div>

@endsection


@extends('layouts.app')

@section('title', 'Tambah Produk - Inventory Seblak Asgar')

@section('content')

<div class="page-header">
    <h1>Tambah Produk Menu</h1>
    <p>Tambahkan menu makanan baru ke dalam sistem inventory.</p>
</div>

<p>
    <a href="{{ route('products.index') }}">← Kembali ke Produk Menu</a>
</p>

@if ($errors->any())
    <div style="color: red;">
        @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

<form action="{{ route('products.store') }}" method="POST">
    @csrf

    <div style="margin-bottom: 15px;">
        <label>Kategori Menu</label><br>
        <select name="category_id" required>
            <option value="">-- Pilih Kategori --</option>

            @foreach ($categories as $category)
                <option value="{{ $category->id }}"
                    {{ old('category_id') == $category->id ? 'selected' : '' }}>
                    {{ $category->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div style="margin-bottom: 15px;">
        <label>Kode Produk</label><br>
        <input type="text"
               name="code"
               value="{{ old('code') }}"
               placeholder="Contoh: MNU001"
               required>
    </div>

    <div style="margin-bottom: 15px;">
        <label>Nama Produk</label><br>
        <input type="text"
               name="name"
               value="{{ old('name') }}"
               placeholder="Contoh: Seblak Original"
               required>
    </div>

    <div style="margin-bottom: 15px;">
        <label>Deskripsi</label><br>
        <textarea name="description"
                  rows="4">{{ old('description') }}</textarea>
    </div>

    <div style="margin-bottom: 15px;">
        <label>Harga Jual (Rp)</label><br>
        <input type="number"
               name="selling_price"
               value="{{ old('selling_price') }}"
               min="0"
               step="0.01"
               required>
    </div>

    <button type="submit">Simpan Produk</button>
</form>

@endsection

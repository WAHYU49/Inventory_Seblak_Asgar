
@extends('layouts.app')

@section('title', 'Edit Produk - Inventory Seblak Asgar')

@section('content')

<div class="page-header">
    <h1>Edit Produk Menu</h1>
    <p>Perbarui informasi produk dan harga jual.</p>
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

<form action="{{ route('products.update', $product->id) }}" method="POST">
    @csrf
    @method('PUT')

    <div style="margin-bottom: 15px;">
        <label>Kategori Menu</label><br>
        <select name="category_id" required>
            <option value="">-- Pilih Kategori --</option>

            @foreach ($categories as $category)
                <option value="{{ $category->id }}"
                    {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>
                    {{ $category->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div style="margin-bottom: 15px;">
        <label>Kode Produk</label><br>
        <input type="text"
               name="code"
               value="{{ old('code', $product->code) }}"
               required>
    </div>

    <div style="margin-bottom: 15px;">
        <label>Nama Produk</label><br>
        <input type="text"
               name="name"
               value="{{ old('name', $product->name) }}"
               required>
    </div>

    <div style="margin-bottom: 15px;">
        <label>Deskripsi</label><br>
        <textarea name="description"
                  rows="4">{{ old('description', $product->description) }}</textarea>
    </div>

    <div style="margin-bottom: 15px;">
        <label>Harga Jual (Rp)</label><br>
        <input type="number"
               name="selling_price"
               value="{{ old('selling_price', $product->selling_price) }}"
               min="0"
               step="0.01"
               required>
    </div>

    <button type="submit">Simpan Perubahan</button>
</form>

@endsection

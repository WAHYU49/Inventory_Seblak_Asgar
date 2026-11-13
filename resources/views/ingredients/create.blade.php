@extends('layouts.app')

@section('title', 'Tambah Bahan Baku - Inventory Seblak Asgar')

@section('content')
<div class="page-header">
    <h1>Tambah Bahan Baku</h1>
    <p>Daftarkan bahan baku, satuan, dan stok awal.</p>
</div>
<p><a href="{{ route('ingredients.index') }}" class="btn btn-secondary">← Kembali ke Bahan Baku</a></p>

@if ($errors->any())
    <div class="alert alert-danger" role="alert">
        <strong>Periksa kembali isian berikut:</strong>
        @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

<div class="content-card">
    <form action="{{ route('ingredients.store') }}" method="POST">
        @csrf

        <div class="form-group">
            <label for="code">Kode Bahan Baku</label>
            <input id="code" type="text" name="code" value="{{ old('code') }}"
                placeholder="Contoh: BB001" required>
            @error('code') <small class="text-danger" style="display:block; color:#b91c1c;">{{ $message }}</small> @enderror
        </div>

        <div class="form-group">
            <label for="name">Nama Bahan Baku</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}"
                placeholder="Contoh: Kerupuk Seblak" required>
            @error('name') <small class="text-danger" style="display:block; color:#b91c1c;">{{ $message }}</small> @enderror
        </div>

        <div class="form-group">
            <label for="category">Kategori</label>
            <input id="category" type="text" name="category" value="{{ old('category') }}"
                placeholder="Contoh: Bumbu">
            @error('category') <small class="text-danger" style="display:block; color:#b91c1c;">{{ $message }}</small> @enderror
        </div>

        <div class="form-group">
            <label for="unit">Satuan</label>
            <input id="unit" type="text" name="unit" value="{{ old('unit') }}"
                placeholder="Contoh: gram, kg, pcs, bungkus" required>
            @error('unit') <small class="text-danger" style="display:block; color:#b91c1c;">{{ $message }}</small> @enderror
        </div>

        <div class="form-group">
            <label for="current_stock">Stok Awal</label>
            <input id="current_stock" type="number" name="current_stock"
                value="{{ old('current_stock', 0) }}" placeholder="Contoh: 0"
                min="0" step="0.001" required>
            @error('current_stock') <small class="text-danger" style="display:block; color:#b91c1c;">{{ $message }}</small> @enderror
        </div>

        <div class="form-group">
            <label for="minimum_stock">Minimum Stok</label>
            <input id="minimum_stock" type="number" name="minimum_stock"
                value="{{ old('minimum_stock', 0) }}" placeholder="Contoh: 5"
                min="0" step="0.001" required>
            @error('minimum_stock') <small class="text-danger" style="display:block; color:#b91c1c;">{{ $message }}</small> @enderror
        </div>

        <button type="submit" class="btn btn-primary">Simpan Bahan Baku</button>
        <a href="{{ route('ingredients.index') }}" class="btn btn-secondary">Batal</a>
    </form>
</div>
@endsection
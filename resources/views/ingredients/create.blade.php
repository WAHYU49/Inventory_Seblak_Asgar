@extends('layouts.app')

@section('title', 'Tambah Bahan Baku - Inventory Seblak Asgar')

@section('content')
<div class="page-header">
    <h1>Tambah Bahan Baku</h1>
    <p>Daftarkan bahan baku, satuan, dan stok awal.</p>
</div>

<p><a href="{{ route('ingredients.index') }}">← Kembali ke Bahan Baku</a></p>

@if ($errors->any())
    <div style="color: red; margin-bottom: 15px;">
        @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

<form action="{{ route('ingredients.store') }}" method="POST">
    @csrf

    <div style="margin-bottom: 15px;">
        <label>Kode Bahan Baku</label><br>
        <input type="text" name="code" value="{{ old('code') }}"
            placeholder="Contoh: BB001" required>
    </div>

    <div style="margin-bottom: 15px;">
        <label>Nama Bahan Baku</label><br>
        <input type="text" name="name" value="{{ old('name') }}"
            placeholder="Contoh: Kerupuk Seblak" required>
    </div>

    <div style="margin-bottom: 15px;">
        <label>Kategori</label><br>
        <input type="text" name="category" value="{{ old('category') }}"
            placeholder="Contoh: Bumbu">
    </div>

    <div style="margin-bottom: 15px;">
        <label>Satuan</label><br>
        <input type="text" name="unit" value="{{ old('unit') }}"
            placeholder="Contoh: gram, kg, pcs, bungkus" required>
    </div>

    <div style="margin-bottom: 15px;">
        <label>Stok Awal</label><br>
        <input type="number" name="current_stock"
            value="{{ old('current_stock', 0) }}"
            min="0" step="0.001" required>
    </div>

    <div style="margin-bottom: 15px;">
        <label>Minimum Stok</label><br>
        <input type="number" name="minimum_stock"
            value="{{ old('minimum_stock', 0) }}"
            min="0" step="0.001" required>
    </div>

    <button type="submit">Simpan Bahan Baku</button>
</form>
@endsection
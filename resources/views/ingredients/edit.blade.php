@extends('layouts.app')

@section('title', 'Edit Bahan Baku - Inventory Seblak Asgar')

@section('content')
<div class="page-header">
    <h1>Edit Bahan Baku</h1>
    <p>Perbarui informasi dan batas minimum stok bahan baku.</p>
</div>

<p><a href="{{ route('ingredients.index') }}">← Kembali ke Bahan Baku</a></p>

@if ($errors->any())
    <div style="color: red; margin-bottom: 15px;">
        @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

<form action="{{ route('ingredients.update', $ingredient->id) }}" method="POST">
    @csrf
    @method('PUT')

    <div style="margin-bottom: 15px;">
        <label>Kode Bahan Baku</label><br>
        <input type="text" name="code"
            value="{{ old('code', $ingredient->code) }}" required>
    </div>

    <div style="margin-bottom: 15px;">
        <label>Nama Bahan Baku</label><br>
        <input type="text" name="name"
            value="{{ old('name', $ingredient->name) }}" required>
    </div>

    <div style="margin-bottom: 15px;">
        <label>Satuan</label><br>
        <input type="text" name="unit"
            value="{{ old('unit', $ingredient->unit) }}" required>
    </div>

    <div style="margin-bottom: 15px;">
        <label>Stok Saat Ini</label><br>
        <input type="text"
            value="{{ $ingredient->current_stock }} {{ $ingredient->unit }}"
            readonly>
        <small>Stok diperbarui melalui transaksi pembelian atau penyesuaian stok.</small>
    </div>

    <div style="margin-bottom: 15px;">
        <label>Minimum Stok</label><br>
        <input type="number" name="minimum_stock"
            value="{{ old('minimum_stock', $ingredient->minimum_stock) }}"
            min="0" step="0.001" required>
    </div>

    <div style="margin-bottom: 15px;">
        <label>Harga Beli</label><br>
        <input type="number" name="purchase_price"
            value="{{ old('purchase_price', $ingredient->purchase_price) }}"
            min="0" step="0.01">
    </div>

    <button type="submit">Simpan Perubahan</button>
</form>
@endsection
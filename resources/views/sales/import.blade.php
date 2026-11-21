@extends('layouts.app')

@section('title', 'Import Penjualan - Inventory Seblak Asgar')

@section('content')
<div class="page-header">
    <h1>Import Hasil Penjualan</h1>
    <p>Unggah file Excel untuk memeriksa hasil penjualan sebelum diproses.</p>
</div>

<p><a href="{{ route('sales.index') }}" class="btn btn-secondary">← Kembali ke Penjualan</a></p>

@if ($errors->any())
    <div class="alert alert-danger" role="alert">
        @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

<div class="content-card">
    <p>File harus memiliki dua kolom pada baris pertama: <strong>Produk</strong> dan <strong>Terjual</strong>.</p>
    <p>Nama produk dicocokkan dengan produk terdaftar. Baris dengan produk yang sama dijumlahkan pada preview.</p>
    <p>Stok tidak berubah sebelum import dikonfirmasi.</p>
</div>

<div class="content-card">
    <form action="{{ route('sales.import.preview') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="form-group">
            <label for="sale_date">Tanggal Penjualan</label>
            <input id="sale_date" type="date" name="sale_date"
                value="{{ old('sale_date', now('Asia/Jakarta')->toDateString()) }}" required
                @error('sale_date') aria-invalid="true" aria-describedby="sale-date-error" @enderror>
            @error('sale_date')
                <small id="sale-date-error" class="field-error">{{ $message }}</small>
            @enderror
        </div>

        <div class="form-group">
            <label for="file">File Excel (.xlsx atau .xls, maksimal 5 MB)</label>
            <input id="file" type="file" name="file" accept=".xlsx,.xls" required
                @error('file') aria-invalid="true" aria-describedby="file-error" @enderror>
            @error('file')
                <small id="file-error" class="field-error">{{ $message }}</small>
            @enderror
        </div>

        <button type="submit" class="btn btn-primary">Validasi dan Preview</button>
    </form>
</div>
@endsection

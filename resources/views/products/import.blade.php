@extends('layouts.app')

@section('title', 'Import Produk - Inventory Seblak Asgar')

@section('content')
<div class="page-header">
    <h1>Import Produk</h1>
    <p>Unggah file Excel untuk memeriksa produk dan resep menu sebelum ditambahkan.</p>
</div>

<p><a href="{{ route('products.index') }}" class="btn btn-secondary">← Kembali ke Produk Menu</a></p>

@if (session('success'))
    <div class="alert alert-success" role="status">{{ session('success') }}</div>
@endif

@if (session('error'))
    <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
@endif

@if ($report = session('import_report'))
    <div class="alert alert-success" role="status">
        Import selesai. {{ $report['created'] }} produk ditambahkan;
        {{ $report['duplicates'] }} produk duplikat dilewati.
        {{ $report['recipes_created'] }} resep dibuat dengan
        {{ $report['recipe_details_created'] }} detail bahan baku;
        {{ $report['recipes_skipped'] }} resep yang sudah tersedia dilewati.
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger" role="alert">
        @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

<div class="content-card">
    <p>File Excel harus memiliki sheet <strong>PRODUK</strong> (kolom Produk, Kategori) dan
        <strong>RESEP MENU</strong> (kolom Produk, Bahan Baku, Jumlah, Satuan).</p>
    <p>Kategori yang belum tersedia dan produk baru hanya dibuat saat konfirmasi.
        Resep hanya menggunakan bahan baku dan satuan operasional yang sudah tersedia.</p>
    <a href="{{ route('products.import.template') }}" class="btn btn-secondary">Unduh Template Excel</a>
</div>

<div class="content-card">
    <form action="{{ route('products.import.preview') }}" method="POST" enctype="multipart/form-data">
        @csrf
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

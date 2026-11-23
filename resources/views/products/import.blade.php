@extends('layouts.app')

@section('title', 'Import Produk - Inventory Seblak Asgar')

@section('content')
<div class="page-header">
    <h1>Import Produk</h1>
    <p>Unggah file Excel untuk memeriksa produk sebelum ditambahkan.</p>
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
        Import berhasil. {{ $report['created'] }} produk ditambahkan.
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
    <p>File harus memiliki dua kolom: <strong>Produk</strong> dan <strong>Kategori</strong>.</p>
    <p>Kategori harus sudah tersedia. Data produk dan bahan baku tetap dikelola terpisah.</p>
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

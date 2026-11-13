@extends('layouts.app')

@section('title', 'Import Bahan Baku - Inventory Seblak Asgar')

@section('content')
<div class="page-header">
    <h1>Import Master Bahan Baku</h1>
    <p>Unggah file Excel untuk memeriksa data terlebih dahulu sebelum disimpan.</p>
</div>

<p><a href="{{ route('ingredients.index') }}" class="btn btn-secondary">← Kembali ke Bahan Baku</a></p>

@if (session('error'))
    <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
@endif

@if ($errors->any())
    <div class="alert alert-danger" role="alert">
        @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

@if ($report = session('import_report'))
    <div class="alert {{ $report['failed'] > 0 ? 'alert-warning' : 'alert-success' }}" role="status">
        <strong>Hasil import</strong>
        <ul>
            <li>Data baru: {{ $report['created'] }}</li>
            <li>Data diperbarui: {{ $report['updated'] }}</li>
            <li>Data dilewati (baris kosong): {{ $report['skipped'] }}</li>
            <li>Data gagal: {{ $report['failed'] }}</li>
        </ul>

        @if ($report['errors'] !== [])
            <strong>Kesalahan per baris</strong>
            <ul>
                @foreach ($report['errors'] as $rowError)
                    <li>Baris {{ $rowError['row'] }}: {{ $rowError['message'] }}</li>
                @endforeach
            </ul>
        @endif
    </div>
@endif

<div class="content-card">
    <p>Gunakan sheet pertama dengan header master bahan baku: SKU ID, Nama Barang, Kategori, Satuan, Jumlah / Isi, supplier, dan Alokasi. Kolom tambahan bersifat opsional.</p>
    <p>Baris tanpa data akan dilewati. Baris tidak valid ditampilkan pada preview. Nilai opsional yang kosong tidak menghapus data master yang sudah tersimpan.</p>
    <p><strong>Import ini hanya memperbarui informasi master.</strong> Stok, stok minimum, dan riwayat stok tidak diubah.</p>
</div>

    <form action="{{ route('ingredients.import.preview') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="form-group">
            <label for="file">File Excel (.xlsx atau .xls, maksimal 5 MB)</label>
            <input id="file" type="file" name="file" accept=".xlsx,.xls" required>
            @error('file') <small class="text-danger" style="display:block; color:#b91c1c;">{{ $message }}</small> @enderror
        </div>
        <button type="submit" class="btn btn-primary">Validasi dan Preview</button>
    </form>
</div>
@endsection

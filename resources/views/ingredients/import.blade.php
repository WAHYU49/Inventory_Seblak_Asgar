@extends('layouts.app')

@section('title', 'Import Bahan Baku - Inventory Seblak Asgar')

@section('content')
<div class="page-header">
    <h1>Import Master Bahan Baku</h1>
    <p>Unggah file Excel untuk memeriksa data terlebih dahulu sebelum disimpan.</p>
</div>

<p><a href="{{ route('ingredients.index') }}">← Kembali ke Bahan Baku</a></p>

@if (session('error'))
    <p style="color: red;">{{ session('error') }}</p>
@endif

@if ($errors->any())
    <div style="color: red; margin-bottom: 15px;">
        @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

@if ($report = session('import_report'))
    <div style="margin: 15px 0; padding: 15px; border: 1px solid #9ab6a2; background: #eef7f0;">
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

<div style="margin-bottom: 20px;">
    <p>Gunakan sheet pertama dengan header master bahan baku: SKU ID, Nama Barang, Kategori, Satuan, Jumlah / Isi, supplier, dan Alokasi. Kolom tambahan bersifat opsional.</p>
    <p>Baris tanpa data akan dilewati. Baris tidak valid ditampilkan pada preview. Nilai opsional yang kosong tidak menghapus data master yang sudah tersimpan.</p>
    <p><strong>Import ini hanya memperbarui informasi master.</strong> Stok, stok minimum, dan riwayat stok tidak diubah.</p>
</div>

<form action="{{ route('ingredients.import.preview') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <div style="margin-bottom: 15px;">
        <label for="file">File Excel (.xlsx atau .xls, maksimal 5 MB)</label><br>
        <input id="file" type="file" name="file" accept=".xlsx,.xls" required>
    </div>
    <button type="submit">Validasi dan Preview</button>
</form>
@endsection


@extends('layouts.app')

@section('title', 'Bahan Baku - Inventory Seblak Asgar')

@section('content')

<div class="page-header">
    <h1>Data Bahan Baku</h1>
    <p>Kelola persediaan bahan baku dan bumbu untuk kebutuhan operasional Seblak Asgar.</p>
</div>

@if (session('success'))
    <p style="color: green;">{{ session('success') }}</p>
@endif

@if (session('error'))
    <p style="color: red;">{{ session('error') }}</p>
@endif

@if ($errors->any())
    <div style="color: red;">
        @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

<p>
    <a href="{{ route('ingredients.create') }}">+ Tambah Bahan Baku</a>
    | <a href="{{ route('ingredients.import') }}">Import Master Bahan Baku</a>
    | <a href="{{ route('ingredients.export') }}">Export Excel</a>
</p>

<table border="1" cellpadding="10" cellspacing="0" width="100%">
    <thead>
        <tr>
            <th>No.</th>
            <th>Kode</th>
            <th>Nama Bahan</th>
            <th>Satuan</th>
            <th>Stok Saat Ini</th>
            <th>Stok Minimum</th>
            <th>Harga Beli</th>
            <th>Status Stok</th>
            <th>Status Data</th>
            <th>Aksi</th>
        </tr>
    </thead>

    <tbody>
        @forelse ($ingredients as $ingredient)
            <tr>
                <td>{{ $loop->iteration + ($ingredients->currentPage() - 1) * $ingredients->perPage() }}</td>

                <td>{{ $ingredient->code }}</td>

                <td>{{ $ingredient->name }}</td>

                <td>{{ $ingredient->unit }}</td>

                <td>{{ number_format($ingredient->current_stock, 3, ',', '.') }}</td>

                <td>{{ number_format($ingredient->minimum_stock, 3, ',', '.') }}</td>

                <td>
                    Rp {{ number_format($ingredient->purchase_price ?? 0, 0, ',', '.') }}
                </td>

                <td>
                    @if ($ingredient->current_stock <= $ingredient->minimum_stock)
                        <span style="color: red; font-weight: bold;">Stok Menipis</span>
                    @else
                        <span style="color: green;">Stok Aman</span>
                    @endif
                </td>

                <td>
                    {{ $ingredient->is_active ? 'Aktif' : 'Nonaktif' }}
                </td>

                <td>
                    <a href="{{ route('ingredients.edit', $ingredient->id) }}">
                        Edit
                    </a>

                    @if ($ingredient->is_active)
                        <form action="{{ route('ingredients.destroy', $ingredient->id) }}"
                              method="POST"
                              style="display: inline;"
                              onsubmit="return confirm('Yakin ingin menonaktifkan bahan baku ini?')">
                            @csrf
                            @method('DELETE')

                            <button type="submit">Nonaktifkan</button>
                        </form>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="10">Belum ada data bahan baku.</td>
            </tr>
        @endforelse
    </tbody>
</table>

<div style="margin-top: 20px;">
    {{ $ingredients->links() }}
</div>

@endsection

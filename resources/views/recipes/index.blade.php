
@extends('layouts.app')

@section('title', 'Resep Menu - Inventory Seblak Asgar')

@section('content')

<div class="page-header">
    <h1>Resep Menu</h1>
    <p>Kelola komposisi bahan baku untuk setiap menu makanan Seblak Asgar.</p>
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
    <a href="{{ route('recipes.create') }}">+ Tambah Resep</a>
</p>

<table border="1" cellpadding="10" cellspacing="0" width="100%">
    <thead>
        <tr>
            <th>No.</th>
            <th>Nama Menu</th>
            <th>Nama Resep</th>
            <th>Komposisi Bahan</th>
            <th>Status</th>
            <th>Tanggal Input</th>
            <th>Aksi</th>
        </tr>
    </thead>

    <tbody>
        @forelse ($recipes as $recipe)
            <tr>
                <td>
                    {{ $loop->iteration + ($recipes->currentPage() - 1) * $recipes->perPage() }}
                </td>

                <td>{{ $recipe->product->name ?? '-' }}</td>

                <td>{{ $recipe->name }}</td>

                <td>
                    @foreach ($recipe->details as $detail)
                        {{ $detail->ingredient->name ?? 'Bahan tidak ditemukan' }}
                        ({{ \App\Support\InventoryNumberFormatter::format($detail->quantity) }} {{ $detail->ingredient->unit ?? '' }})
                        <br>
                    @endforeach
                </td>

                <td>
                    {{ $recipe->is_active ? 'Aktif' : 'Nonaktif' }}
                </td>

                <td>{{ $recipe->created_at->format('d-m-Y H:i') }}</td>

                <td>
                    <a href="{{ route('recipes.edit', $recipe->id) }}">Edit</a>

                    <form action="{{ route('recipes.destroy', $recipe->id) }}"
                          method="POST"
                          style="display: inline;"
                          onsubmit="return confirm('Yakin ingin menghapus resep ini?')">
                        @csrf
                        @method('DELETE')

                        <button type="submit">Hapus</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7">Belum ada data resep.</td>
            </tr>
        @endforelse
    </tbody>
</table>

<div style="margin-top: 20px;">
    {{ $recipes->links() }}
</div>

@endsection


<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Resep - Inventory Seblak Asgar</title>
</head>
<body>

    @include('layouts.navbar')

    <div class="main-content">

    <h1>Daftar Resep Menu</h1>

    <p>
        <a href="{{ route('recipes.create') }}">+ Tambah Resep</a>
        |
        <a href="{{ route('products.index') }}">Data Produk</a>
        |
        <a href="{{ route('ingredients.index') }}">Data Bahan Baku</a>
    </p>

    @if (session('success'))
        <p style="color: green;">
            {{ session('success') }}
        </p>
    @endif

    @if (session('error'))
        <p style="color: red;">
            {{ session('error') }}
        </p>
    @endif

    @if ($recipes->count() > 0)
        <table border="1" cellpadding="10" cellspacing="0">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Nama Menu</th>
                    <th>Nama Resep</th>
                    <th>Komposisi Bahan</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($recipes as $recipe)
                    <tr>
                        <td>{{ $loop->iteration + ($recipes->currentPage() - 1) * $recipes->perPage() }}</td>

                        <td>
                            {{ $recipe->product->name ?? '-' }}
                        </td>

                        <td>{{ $recipe->name }}</td>

                        <td>
                            @foreach ($recipe->details as $detail)
                                {{ $detail->ingredient->name ?? 'Bahan tidak ditemukan' }}
                                ({{ $detail->quantity }} {{ $detail->ingredient->unit ?? '' }})
                                <br>
                            @endforeach
                        </td>

                        <td>
                            {{ $recipe->is_active ? 'Aktif' : 'Nonaktif' }}
                        </td>

                        <td>
                            <a href="{{ route('recipes.edit', $recipe->id) }}">
                                Edit
                            </a>

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
                @endforeach
            </tbody>
        </table>

        <div style="margin-top: 20px;">
            {{ $recipes->links() }}
        </div>
    @else
        <p>Belum ada data resep. Silakan tambahkan resep menu terlebih dahulu.</p>
    @endif

    </div>

</body>
</html>

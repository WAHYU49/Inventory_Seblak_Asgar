<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Kategori - Inventory Seblak Asgar</title>
</head>
<body>

    @include('layouts.navbar')

    <div class="main-content">

    <h1>Data Kategori</h1>

    @if(session('success'))
        <p style="color: green">{{ session('success') }}</p>
    @endif

    @if(session('error'))
        <p style="color: red">{{ session('error') }}</p>
    @endif

    <a href="{{ route('categories.create') }}">+ Tambah Kategori</a>

    <br><br>

    <table border="1" cellpadding="10" cellspacing="0">
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Kategori</th>
                <th>Deskripsi</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>

        <tbody>
            @forelse($categories as $category)
                <tr>
                    <td>{{ $categories->firstItem() + $loop->index }}</td>
                    <td>{{ $category->name }}</td>
                    <td>{{ $category->description ?? '-' }}</td>
                    <td>{{ $category->is_active ? 'Aktif' : 'Tidak Aktif' }}</td>
                    <td>
                        <a href="{{ route('categories.edit', $category) }}">Edit</a>

                        <form action="{{ route('categories.destroy', $category) }}"
                              method="POST"
                              style="display:inline">

                            @csrf
                            @method('DELETE')

                            <button type="submit"
                                onclick="return confirm('Yakin ingin menghapus kategori ini?')">
                                Hapus
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">Belum ada data kategori.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <br>

    {{ $categories->links() }}
    
</div>

</body>
</html>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Produk - Inventory Seblak Asgar</title>
</head>
<body>

    @include('layouts.navbar')

    <div class="main-content">

    <h1>Tambah Produk</h1>

    <a href="{{ route('products.index') }}">Kembali</a>

    @if ($errors->any())
        <div style="color: red;">
            <strong>Terjadi kesalahan:</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('products.store') }}" method="POST">
        @csrf

        <div>
            <label>Kategori Produk</label><br>
            <select name="category_id" required>
                <option value="">-- Pilih Kategori --</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}"
                        {{ old('category_id') == $category->id ? 'selected' : '' }}>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <br>

        <div>
            <label>Kode Produk</label><br>
            <input type="text" name="code"
                   value="{{ old('code') }}"
                   placeholder="Contoh: MKN001" required>
        </div>

        <br>

        <div>
            <label>Nama Produk</label><br>
            <input type="text" name="name"
                   value="{{ old('name') }}"
                   placeholder="Contoh: Seblak Original" required>
        </div>

        <br>

        <div>
            <label>Deskripsi</label><br>
            <textarea name="description"
                      rows="4"
                      placeholder="Deskripsi produk">{{ old('description') }}</textarea>
        </div>

        <br>

        <div>
            <label>Harga Jual (Rp)</label><br>
            <input type="number" name="selling_price"
                   value="{{ old('selling_price') }}"
                   min="0" step="1"
                   placeholder="Contoh: 15000" required>
        </div>

        <br>

        <div>
            <label>Lokasi Gambar (Opsional)</label><br>
            <input type="text" name="image"
                   value="{{ old('image') }}"
                   placeholder="Contoh: products/seblak.jpg">
        </div>

        <br>

        <button type="submit">Simpan Produk</button>
    </form>
    </div>

</body>
</html>

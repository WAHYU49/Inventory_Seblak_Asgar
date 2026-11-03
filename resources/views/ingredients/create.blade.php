
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Bahan Baku - Inventory Seblak Asgar</title>
</head>
<body>

    @include('layouts.navbar')

    <div class="main-content">

    <h1>Tambah Bahan Baku</h1>

    <a href="{{ route('ingredients.index') }}">Kembali</a>

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

    <form action="{{ route('ingredients.store') }}" method="POST">
        @csrf

        <div>
            <label>Kode Bahan Baku</label><br>
            <input type="text"
                   name="code"
                   value="{{ old('code') }}"
                   placeholder="Contoh: BB001"
                   required>
        </div>

        <br>

        <div>
            <label>Nama Bahan Baku</label><br>
            <input type="text"
                   name="name"
                   value="{{ old('name') }}"
                   placeholder="Contoh: Kerupuk Seblak"
                   required>
        </div>

        <br>

        <div>
            <label>Satuan</label><br>
            <select name="unit" required>
                <option value="">-- Pilih Satuan --</option>
                <option value="gram" {{ old('unit') == 'gram' ? 'selected' : '' }}>Gram</option>
                <option value="kg" {{ old('unit') == 'kg' ? 'selected' : '' }}>Kilogram</option>
                <option value="ml" {{ old('unit') == 'ml' ? 'selected' : '' }}>Mililiter</option>
                <option value="liter" {{ old('unit') == 'liter' ? 'selected' : '' }}>Liter</option>
                <option value="pcs" {{ old('unit') == 'pcs' ? 'selected' : '' }}>Pcs</option>
                <option value="butir" {{ old('unit') == 'butir' ? 'selected' : '' }}>Butir</option>
                <option value="bungkus" {{ old('unit') == 'bungkus' ? 'selected' : '' }}>Bungkus</option>
                <option value="ikat" {{ old('unit') == 'ikat' ? 'selected' : '' }}>Ikat</option>
            </select>
        </div>

        <br>

        <div>
            <label>Stok Awal</label><br>
            <input type="number"
                   name="current_stock"
                   value="{{ old('current_stock', 0) }}"
                   min="0"
                   step="0.001"
                   required>
        </div>

        <br>

        <div>
            <label>Stok Minimum</label><br>
            <input type="number"
                   name="minimum_stock"
                   value="{{ old('minimum_stock', 0) }}"
                   min="0"
                   step="0.001"
                   required>
        </div>

        <br>

        <div>
            <label>Harga Beli (Rp)</label><br>
            <input type="number"
                   name="purchase_price"
                   value="{{ old('purchase_price') }}"
                   min="0"
                   step="0.01"
                   placeholder="Contoh: 25000">
        </div>

        <br>

        <button type="submit">Simpan Bahan Baku</button>

    </form>
    </div>

</body>
</html>


<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Bahan Baku - Inventory Seblak Asgar</title>
</head>
<body>

    @include('layouts.navbar')

    <div class="main-content">

    <h1>Edit Bahan Baku</h1>

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

    <form action="{{ route('ingredients.update', $ingredient->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div>
            <label>Kode Bahan Baku</label><br>
            <input type="text"
                   name="code"
                   value="{{ old('code', $ingredient->code) }}"
                   required>
        </div>

        <br>

        <div>
            <label>Nama Bahan Baku</label><br>
            <input type="text"
                   name="name"
                   value="{{ old('name', $ingredient->name) }}"
                   required>
        </div>

        <br>

        <div>
            <label>Satuan</label><br>
            <select name="unit" required>
                @foreach (['gram', 'kg', 'ml', 'liter', 'pcs', 'butir', 'bungkus', 'ikat'] as $unit)
                    <option value="{{ $unit }}"
                        {{ old('unit', $ingredient->unit) == $unit ? 'selected' : '' }}>
                        {{ ucfirst($unit) }}
                    </option>
                @endforeach
            </select>
        </div>

        <br>

        <div>
            <label>Stok Saat Ini</label><br>
            <input type="text"
                   value="{{ $ingredient->current_stock }}"
                   readonly>
            <small>Stok diubah melalui pembelian atau penyesuaian stok.</small>
        </div>

        <br>

        <div>
            <label>Stok Minimum</label><br>
            <input type="number"
                   name="minimum_stock"
                   value="{{ old('minimum_stock', $ingredient->minimum_stock) }}"
                   min="0"
                   step="0.001"
                   required>
        </div>

        <br>

        <div>
            <label>Harga Beli (Rp)</label><br>
            <input type="number"
                   name="purchase_price"
                   value="{{ old('purchase_price', $ingredient->purchase_price) }}"
                   min="0"
                   step="0.01">
        </div>

        <br>

        <button type="submit">Simpan Perubahan</button>

    </form>
    </div>

</body>
</html>

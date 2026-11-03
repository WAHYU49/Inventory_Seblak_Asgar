
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Resep - Inventory Seblak Asgar</title>
</head>
<body>

    @include('layouts.navbar')

    <div class="main-content">

    <h1>Tambah Resep Menu</h1>

    <p>
        <a href="{{ route('recipes.index') }}">Kembali ke Daftar Resep</a>
    </p>

    @if ($errors->any())
        <div style="color: red;">
            <strong>Periksa kembali data berikut:</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('recipes.store') }}" method="POST">
        @csrf

        <div style="margin-bottom: 15px;">
            <label>Produk Menu</label><br>
            <select name="product_id" required>
                <option value="">-- Pilih Produk --</option>

                @foreach ($products as $product)
                    <option value="{{ $product->id }}"
                        {{ old('product_id') == $product->id ? 'selected' : '' }}>
                        {{ $product->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div style="margin-bottom: 15px;">
            <label>Nama Resep</label><br>
            <input type="text"
                   name="name"
                   value="{{ old('name') }}"
                   placeholder="Contoh: Resep Seblak Original"
                   required>
        </div>

        <div style="margin-bottom: 15px;">
            <label>Keterangan</label><br>
            <textarea name="description"
                      rows="3"
                      placeholder="Keterangan resep">{{ old('description') }}</textarea>
        </div>

        <hr>

        <h3>Komposisi Bahan Baku</h3>

        <div id="ingredient-container">

            <div class="ingredient-row" style="margin-bottom: 15px;">
                <select name="details[0][ingredient_id]" required>
                    <option value="">-- Pilih Bahan Baku --</option>

                    @foreach ($ingredients as $ingredient)
                        <option value="{{ $ingredient->id }}">
                            {{ $ingredient->name }} ({{ $ingredient->unit }})
                        </option>
                    @endforeach
                </select>

                <input type="number"
                       name="details[0][quantity]"
                       placeholder="Jumlah"
                       min="0.001"
                       step="0.001"
                       required>

                <button type="button" onclick="removeIngredient(this)">
                    Hapus
                </button>
            </div>

        </div>

        <button type="button" onclick="addIngredient()">
            + Tambah Bahan
        </button>

        <br><br>

        <button type="submit">Simpan Resep</button>
    </form>

    <script>
        let ingredientIndex = 1;

        function addIngredient() {
            const container = document.getElementById('ingredient-container');

            const row = document.createElement('div');
            row.className = 'ingredient-row';
            row.style.marginBottom = '15px';

            row.innerHTML = `
                <select name="details[${ingredientIndex}][ingredient_id]" required>
                    <option value="">-- Pilih Bahan Baku --</option>

                    @foreach ($ingredients as $ingredient)
                        <option value="{{ $ingredient->id }}">
                            {{ $ingredient->name }} ({{ $ingredient->unit }})
                        </option>
                    @endforeach
                </select>

                <input type="number"
                       name="details[${ingredientIndex}][quantity]"
                       placeholder="Jumlah"
                       min="0.001"
                       step="0.001"
                       required>

                <button type="button" onclick="removeIngredient(this)">
                    Hapus
                </button>
            `;

            container.appendChild(row);
            ingredientIndex++;
        }

        function removeIngredient(button) {
            const container = document.getElementById('ingredient-container');

            if (container.children.length > 1) {
                button.closest('.ingredient-row').remove();
            } else {
                alert('Minimal harus ada satu bahan baku.');
            }
        }
    </script>
    </div>

</body>
</html>

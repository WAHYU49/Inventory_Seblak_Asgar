@extends('layouts.app')

@section('title', 'Tambah Resep - Inventory Seblak Asgar')

@section('content')
<style>
    .ingredient-row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) auto;
        gap: 10px;
        align-items: end;
        margin-bottom: 12px;
    }

    .ingredient-row .form-group {
        min-width: 0;
    }

    @media (max-width: 600px) {
        .ingredient-row {
            grid-template-columns: minmax(0, 1fr);
        }
    }
</style>

<div class="page-header">
    <h1>Tambah Resep Menu</h1>
    <p>Tentukan komposisi bahan baku yang dibutuhkan untuk setiap menu.</p>
</div>
<p><a href="{{ route('recipes.index') }}" class="btn btn-secondary">← Kembali ke Resep Menu</a></p>

@if ($errors->any())
    <div class="alert alert-danger" role="alert">
        <strong>Periksa kembali isian berikut:</strong>
        @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

<div class="content-card">
    <form action="{{ route('recipes.store') }}" method="POST">
        @csrf

        <div class="form-group">
            <label for="product_id">Produk Menu</label>
            <select id="product_id" name="product_id" required>
                <option value="">-- Pilih Produk --</option>
                @foreach ($products as $product)
                    <option value="{{ $product->id }}"
                        {{ old('product_id') == $product->id ? 'selected' : '' }}>
                        {{ $product->name }}
                    </option>
                @endforeach
            </select>
            @error('product_id') <small class="text-danger" style="display:block; color:#b91c1c;">{{ $message }}</small> @enderror
        </div>

        <div class="form-group">
            <label for="name">Nama Resep</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}"
                placeholder="Contoh: Resep Seblak Original" required>
            @error('name') <small class="text-danger" style="display:block; color:#b91c1c;">{{ $message }}</small> @enderror
        </div>

        <div class="form-group">
            <label for="description">Deskripsi</label>
            <textarea id="description" name="description" rows="3"
                placeholder="Keterangan resep (opsional)">{{ old('description') }}</textarea>
            @error('description') <small class="text-danger" style="display:block; color:#b91c1c;">{{ $message }}</small> @enderror
        </div>

        <hr>

        <h3>Komposisi Bahan Baku</h3>

        <div id="ingredient-container">
            <div class="ingredient-row">
                <div class="form-group">
                    <label for="ingredient-0">Bahan Baku</label>
                    <select id="ingredient-0" name="details[0][ingredient_id]" required>
                        <option value="">-- Pilih Bahan Baku --</option>
                        @foreach ($ingredients as $ingredient)
                            <option value="{{ $ingredient->id }}"
                                {{ old('details.0.ingredient_id') == $ingredient->id ? 'selected' : '' }}>
                                {{ $ingredient->name }} ({{ $ingredient->unit }})
                            </option>
                        @endforeach
                    </select>
                    @error('details.0.ingredient_id') <small class="text-danger" style="display:block; color:#b91c1c;">{{ $message }}</small> @enderror
                </div>

                <div class="form-group">
                    <label for="quantity-0">Jumlah per Porsi</label>
                    <input id="quantity-0" type="number" name="details[0][quantity]"
                        value="{{ old('details.0.quantity') }}" placeholder="Contoh: 0,025"
                        min="0.001" step="0.001" required>
                    @error('details.0.quantity') <small class="text-danger" style="display:block; color:#b91c1c;">{{ $message }}</small> @enderror
                </div>

                <button type="button" class="btn btn-danger" onclick="removeIngredient(this)">Hapus</button>
            </div>
        </div>

        <button type="button" class="btn btn-secondary" onclick="addIngredient()">
            + Tambah Bahan Baku
        </button>

        <div style="margin-top:20px;">
            <button type="submit" class="btn btn-primary">Simpan Resep</button>
            <a href="{{ route('recipes.index') }}" class="btn btn-secondary">Batal</a>
        </div>
    </form>
</div>

<script>
    let ingredientIndex = 1;

    function addIngredient() {
        const container = document.getElementById('ingredient-container');

        const row = document.createElement('div');
        row.className = 'ingredient-row';
        row.innerHTML = `
            <div class="form-group">
                <label for="ingredient-${ingredientIndex}">Bahan Baku</label>
                <select id="ingredient-${ingredientIndex}" name="details[${ingredientIndex}][ingredient_id]" required>
                    <option value="">-- Pilih Bahan Baku --</option>
                    @foreach ($ingredients as $ingredient)
                        <option value="{{ $ingredient->id }}">
                            {{ $ingredient->name }} ({{ $ingredient->unit }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="quantity-${ingredientIndex}">Jumlah per Porsi</label>
                <input id="quantity-${ingredientIndex}" type="number"
                    name="details[${ingredientIndex}][quantity]" placeholder="Contoh: 0,025"
                    min="0.001" step="0.001" required>
            </div>
            <button type="button" class="btn btn-danger" onclick="removeIngredient(this)">Hapus</button>
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
@endsection
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

@if ($products->isEmpty() || $ingredients->isEmpty())
    <div class="alert alert-info" role="status">
        <strong>Data prasyarat belum lengkap.</strong>
        <ul>
            @if ($products->isEmpty())
                <li>Tambahkan produk menu terlebih dahulu.</li>
            @endif
            @if ($ingredients->isEmpty())
                <li>Tambahkan bahan baku terlebih dahulu.</li>
            @endif
        </ul>
        <div class="form-actions">
            @if ($products->isEmpty())
                <a href="{{ route('products.create') }}" class="btn btn-primary">Tambah Produk</a>
            @endif
            @if ($ingredients->isEmpty())
                <a href="{{ route('ingredients.create') }}" class="btn btn-secondary">Tambah Bahan Baku</a>
            @endif
        </div>
    </div>
@else
<div class="content-card">
    <form action="{{ route('recipes.store') }}" method="POST">
        @csrf

        <div class="form-group">
            <label for="product_id">Produk Menu</label>
            <select id="product_id" name="product_id" required
                @error('product_id') aria-invalid="true" aria-describedby="product-id-error" @enderror>
                <option value="">-- Pilih Produk --</option>
                @foreach ($products as $product)
                    <option value="{{ $product->id }}"
                        {{ old('product_id') == $product->id ? 'selected' : '' }}>
                        {{ $product->name }}
                    </option>
                @endforeach
            </select>
            @error('product_id') <small id="product-id-error" class="field-error">{{ $message }}</small> @enderror
        </div>

        <div class="form-group">
            <label for="name">Nama Resep</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}"
                placeholder="Contoh: Resep Seblak Original" required
                @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
            @error('name') <small id="name-error" class="field-error">{{ $message }}</small> @enderror
        </div>

        <div class="form-group">
            <label for="description">Deskripsi</label>
            <textarea id="description" name="description" rows="3"
                placeholder="Keterangan resep (opsional)"
                @error('description') aria-invalid="true" aria-describedby="description-error" @enderror>{{ old('description') }}</textarea>
            @error('description') <small id="description-error" class="field-error">{{ $message }}</small> @enderror
        </div>

        <hr>

        <h3>Komposisi Bahan Baku</h3>

        <div id="ingredient-container">
            @php
                $detailRows = old('details', [[]]);
                if ($detailRows === []) {
                    $detailRows = [[]];
                }
                $numericIndexes = array_filter(array_keys($detailRows), 'is_int');
                $ingredientIndex = $numericIndexes === [] ? count($detailRows) : max($numericIndexes) + 1;
            @endphp
            @foreach ($detailRows as $index => $detail)
            <div class="ingredient-row">
                <div class="form-group">
                    <label for="ingredient-{{ $index }}">Bahan Baku</label>
                    <select id="ingredient-{{ $index }}" name="details[{{ $index }}][ingredient_id]" required
                        @error('details.' . $index . '.ingredient_id') aria-invalid="true" aria-describedby="ingredient-{{ $index }}-error" @enderror>
                        <option value="">-- Pilih Bahan Baku --</option>
                        @foreach ($ingredients as $ingredient)
                            <option value="{{ $ingredient->id }}"
                                {{ old('details.' . $index . '.ingredient_id') == $ingredient->id ? 'selected' : '' }}>
                                {{ $ingredient->name }} ({{ $ingredient->unit }})
                            </option>
                        @endforeach
                    </select>
                    @error('details.' . $index . '.ingredient_id') <small id="ingredient-{{ $index }}-error" class="field-error">{{ $message }}</small> @enderror
                </div>

                <div class="form-group">
                    <label for="quantity-{{ $index }}">Jumlah per Porsi</label>
                    <input id="quantity-{{ $index }}" type="number" name="details[{{ $index }}][quantity]"
                        value="{{ old('details.' . $index . '.quantity') }}" placeholder="Contoh: 0,025"
                        min="0.001" step="0.001" required
                        @error('details.' . $index . '.quantity') aria-invalid="true" aria-describedby="quantity-{{ $index }}-error" @enderror>
                    @error('details.' . $index . '.quantity') <small id="quantity-{{ $index }}-error" class="field-error">{{ $message }}</small> @enderror
                </div>

                <button type="button" class="btn btn-danger" onclick="removeIngredient(this)">Hapus</button>
            </div>
            @endforeach
        </div>

        <button type="button" class="btn btn-secondary" onclick="addIngredient()">
            + Tambah Bahan Baku
        </button>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Simpan Resep</button>
            <a href="{{ route('recipes.index') }}" class="btn btn-secondary">Batal</a>
        </div>
    </form>
</div>
@endif

<script>
    let ingredientIndex = {{ $ingredientIndex ?? 1 }};

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
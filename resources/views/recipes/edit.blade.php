@extends('layouts.app')

@section('title', 'Edit Resep - Inventory Seblak Asgar')

@section('content')
<div class="page-header">
    <h1>Edit Resep Menu</h1>
    <p>Perbarui komposisi bahan baku dan kebutuhan setiap menu.</p>
</div>

<p><a href="{{ route('recipes.index') }}">← Kembali ke Resep Menu</a></p>

@if ($errors->any())
    <div style="color: red; margin-bottom: 15px;">
        @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

<form action="{{ route('recipes.update', $recipe->id) }}" method="POST">
    @csrf
    @method('PUT')

    <div style="margin-bottom: 15px;">
        <label>Produk Menu</label><br>
        <input type="text" value="{{ $recipe->product->name }}" readonly>
    </div>

    <div style="margin-bottom: 15px;">
        <label>Nama Resep</label><br>
        <input type="text" name="name"
            value="{{ old('name', $recipe->name) }}" required>
    </div>

    <div style="margin-bottom: 15px;">
        <label>Deskripsi</label><br>
        <textarea name="description" rows="3">{{ old('description', $recipe->description) }}</textarea>
    </div>

    <hr>

    <h3>Komposisi Bahan Baku</h3>

    <div id="ingredient-container">
        @foreach ($recipe->details as $index => $detail)
            <div class="ingredient-row"
                style="display:flex; gap:10px; margin-bottom:10px; align-items:center;">

                <select name="details[{{ $index }}][ingredient_id]" required>
                    <option value="">-- Pilih Bahan Baku --</option>

                    @foreach ($ingredients as $ingredient)
                        <option value="{{ $ingredient->id }}"
                            {{ old("details.$index.ingredient_id", $detail->ingredient_id) == $ingredient->id ? 'selected' : '' }}>
                            {{ $ingredient->name }} ({{ $ingredient->unit }})
                        </option>
                    @endforeach
                </select>

                <input type="number"
                    name="details[{{ $index }}][quantity]"
                    value="{{ old("details.$index.quantity", $detail->quantity) }}"
                    placeholder="Jumlah"
                    min="0.001"
                    step="0.001"
                    required>

                <button type="button" onclick="removeIngredient(this)">Hapus</button>
            </div>
        @endforeach
    </div>

    <button type="button" onclick="addIngredient()" style="margin-bottom:20px;">
        + Tambah Bahan Baku
    </button>

    <br>

    <button type="submit">Simpan Perubahan</button>
</form>

<script>
    let ingredientIndex = {{ $recipe->details->count() }};

    function addIngredient() {
        const container = document.getElementById('ingredient-container');

        const row = document.createElement('div');
        row.className = 'ingredient-row';
        row.style.cssText = 'display:flex; gap:10px; margin-bottom:10px; align-items:center;';

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

            <button type="button" onclick="removeIngredient(this)">Hapus</button>
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
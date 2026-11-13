@extends('layouts.app')

@section('title', 'Tambah Pembelian - Inventory Seblak Asgar')

@section('content')
<style>
    .purchase-row {
        display: grid;
        grid-template-columns: minmax(0, 2fr) minmax(0, 1fr) auto;
        gap: 12px;
        align-items: end;
        margin-bottom: 12px;
    }

    .purchase-row > div {
        min-width: 0;
    }

    @media (max-width: 600px) {
        .purchase-row {
            grid-template-columns: minmax(0, 1fr);
        }
    }
</style>

<div class="page-header">
    <h1>Tambah Pembelian Bahan Baku</h1>
    <p>Catat pembelian bahan baku untuk menambah persediaan inventory.</p>
</div>
<p><a href="{{ route('purchases.index') }}" class="btn btn-secondary">← Kembali ke Pembelian</a></p>

@if ($errors->any())
    <div class="alert alert-danger" role="alert">
        <strong>Periksa kembali isian berikut:</strong>
        @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

<form action="{{ route('purchases.store') }}" method="POST">
    @csrf
    <input type="hidden" name="idempotency_key" value="{{ $idempotencyKey }}">

    <div class="content-card">
        <h3>Informasi Pembelian</h3>

        <div class="form-group">
            <label for="purchase_date">Tanggal Pembelian</label>
            <input id="purchase_date" type="date" name="purchase_date"
                value="{{ old('purchase_date', now('Asia/Jakarta')->toDateString()) }}" required>
            @error('purchase_date') <small class="text-danger" style="display:block; color:#b91c1c;">{{ $message }}</small> @enderror
        </div>

        <div class="form-group">
            <label for="notes">Catatan (Opsional)</label>
            <textarea id="notes" name="notes" rows="3"
                placeholder="Contoh: Pembelian bahan baku dari supplier">{{ old('notes') }}</textarea>
            @error('notes') <small class="text-danger" style="display:block; color:#b91c1c;">{{ $message }}</small> @enderror
        </div>
    </div>

    <div class="content-card">
        <h3>Detail Bahan Baku</h3>

        <div id="purchase-container">
            <div class="purchase-row">
                <div class="form-group">
                    <label for="purchase-ingredient-0">Bahan Baku</label>
                    <select id="purchase-ingredient-0" name="details[0][ingredient_id]" required>
                        <option value="">-- Pilih Bahan --</option>
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
                    <label for="purchase-quantity-0">Jumlah</label>
                    <input id="purchase-quantity-0" type="number" name="details[0][quantity]"
                        value="{{ old('details.0.quantity') }}" min="0.001" step="0.001"
                        placeholder="Contoh: 5" required>
                    @error('details.0.quantity') <small class="text-danger" style="display:block; color:#b91c1c;">{{ $message }}</small> @enderror
                </div>

                <button type="button" class="btn btn-danger" onclick="removePurchase(this)">Hapus</button>
            </div>
        </div>

        <button type="button" class="btn btn-secondary" onclick="addPurchase()">
            + Tambah Bahan Baku
        </button>
    </div>

    <button type="submit" class="btn btn-primary">Simpan Pembelian</button>
    <a href="{{ route('purchases.index') }}" class="btn btn-secondary">Batal</a>
</form>

<script>
    let purchaseIndex = 1;

    function addPurchase() {
        const container = document.getElementById('purchase-container');
        const row = document.createElement('div');
        row.className = 'purchase-row';

        row.innerHTML = `
            <div class="form-group">
                <label for="purchase-ingredient-${purchaseIndex}">Bahan Baku</label>
                <select id="purchase-ingredient-${purchaseIndex}" name="details[${purchaseIndex}][ingredient_id]" required>
                    <option value="">-- Pilih Bahan --</option>
                    @foreach ($ingredients as $ingredient)
                        <option value="{{ $ingredient->id }}">
                            {{ $ingredient->name }} ({{ $ingredient->unit }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="purchase-quantity-${purchaseIndex}">Jumlah</label>
                <input id="purchase-quantity-${purchaseIndex}" type="number"
                    name="details[${purchaseIndex}][quantity]" min="0.001" step="0.001"
                    placeholder="Contoh: 5" required>
            </div>
            <button type="button" class="btn btn-danger" onclick="removePurchase(this)">Hapus</button>
        `;

        container.appendChild(row);
        purchaseIndex++;
    }

    function removePurchase(button) {
        const container = document.getElementById('purchase-container');

        if (container.children.length > 1) {
            button.closest('.purchase-row').remove();
        } else {
            alert('Minimal harus ada satu bahan baku.');
        }
    }
</script>
@endsection

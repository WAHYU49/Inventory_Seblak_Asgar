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
    @php
        $hasIdempotencyKey = isset($idempotencyKey)
            && is_string($idempotencyKey)
            && $idempotencyKey !== '';
    @endphp
    @if ($hasIdempotencyKey)
        <input type="hidden" name="idempotency_key" value="{{ $idempotencyKey }}">
    @else
        <div class="alert alert-danger" role="alert">
            Token transaksi tidak tersedia. Muat ulang halaman sebelum menyimpan pembelian.
        </div>
    @endif

    <div class="content-card">
        <h3>Informasi Pembelian</h3>

        <div class="form-group">
            <label for="purchase_date">Tanggal Pembelian</label>
            <input id="purchase_date" type="date" name="purchase_date"
                value="{{ old('purchase_date', now('Asia/Jakarta')->toDateString()) }}" required
                @error('purchase_date') aria-invalid="true" aria-describedby="purchase-date-error" @enderror>
            @error('purchase_date') <small id="purchase-date-error" class="field-error">{{ $message }}</small> @enderror
        </div>

        <div class="form-group">
            <label for="notes">Catatan (Opsional)</label>
            <textarea id="notes" name="notes" rows="3"
                placeholder="Contoh: Pembelian bahan baku dari supplier"
                @error('notes') aria-invalid="true" aria-describedby="notes-error" @enderror>{{ old('notes') }}</textarea>
            @error('notes') <small id="notes-error" class="field-error">{{ $message }}</small> @enderror
        </div>
    </div>

    <div class="content-card">
        <h3>Detail Bahan Baku</h3>

        @if (!$hasActiveIngredients)
            <div class="alert alert-info" role="status">
                <strong>Belum ada bahan baku.</strong>
                <p>Tambahkan bahan baku terlebih dahulu. Pembelian belum dapat disimpan tanpa bahan baku.</p>
                <a href="{{ route('ingredients.create') }}" class="btn btn-primary">Tambah Bahan Baku</a>
            </div>
        @endif

        <div id="purchase-container">
            @php
                $detailRows = old('details', [[]]);
                if ($detailRows === []) {
                    $detailRows = [[]];
                }
                $numericIndexes = array_filter(array_keys($detailRows), 'is_int');
                $purchaseIndex = $numericIndexes === [] ? count($detailRows) : max($numericIndexes) + 1;
            @endphp
            @foreach ($detailRows as $index => $detail)
            @php
                $selectedIngredient = $ingredients->firstWhere(
                    'id',
                    old('details.' . $index . '.ingredient_id')
                );
            @endphp
            <div class="purchase-row">
                <div class="form-group">
                    <label for="purchase-ingredient-search-{{ $index }}">Cari Bahan</label>
                    <div class="autocomplete"
                        data-autocomplete
                        data-autocomplete-url="{{ route('ingredients.autocomplete') }}"
                        data-autocomplete-target="purchase-ingredient-{{ $index }}"
                        data-autocomplete-meta="unit"
                        data-autocomplete-active-only="true">
                        <input id="purchase-ingredient-search-{{ $index }}" type="search"
                            class="autocomplete-input" data-autocomplete-input
                            value="{{ $selectedIngredient?->name ?? '' }}"
                            autocomplete="off" aria-autocomplete="list" aria-expanded="false"
                            aria-controls="purchase-ingredient-suggestions-{{ $index }}"
                            placeholder="Ketik nama bahan...">
                        <div id="purchase-ingredient-suggestions-{{ $index }}"
                            class="autocomplete-suggestions" role="listbox"
                            aria-label="Saran bahan baku" hidden></div>
                    </div>
                    <label for="purchase-ingredient-{{ $index }}">Bahan Baku</label>
                    <select id="purchase-ingredient-{{ $index }}" name="details[{{ $index }}][ingredient_id]" required
                        {{ !$hasActiveIngredients ? 'disabled' : '' }}
                        @error('details.' . $index . '.ingredient_id') aria-invalid="true" aria-describedby="purchase-ingredient-{{ $index }}-error" @enderror>
                        <option value="">-- Pilih Bahan --</option>
                        @if ($selectedIngredient)
                            <option value="{{ $selectedIngredient->id }}" selected>
                                {{ $selectedIngredient->name }} ({{ $selectedIngredient->unit }})
                            </option>
                        @endif
                    </select>
                    @error('details.' . $index . '.ingredient_id') <small id="purchase-ingredient-{{ $index }}-error" class="field-error">{{ $message }}</small> @enderror
                </div>

                <div class="form-group">
                    <label for="purchase-quantity-{{ $index }}">Jumlah</label>
                    <input id="purchase-quantity-{{ $index }}" type="number" name="details[{{ $index }}][quantity]"
                        value="{{ old('details.' . $index . '.quantity') }}" min="0.001" step="0.001"
                        placeholder="Contoh: 5" required
                        @error('details.' . $index . '.quantity') aria-invalid="true" aria-describedby="purchase-quantity-{{ $index }}-error" @enderror>
                    @error('details.' . $index . '.quantity') <small id="purchase-quantity-{{ $index }}-error" class="field-error">{{ $message }}</small> @enderror
                </div>

                <button type="button" class="btn btn-danger" onclick="removePurchase(this)">Hapus</button>
            </div>
            @endforeach
        </div>

        <button type="button" class="btn btn-secondary" onclick="addPurchase()"
            {{ !$hasActiveIngredients ? 'disabled' : '' }}>
            + Tambah Bahan Baku
        </button>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary"
            {{ !$hasActiveIngredients || !$hasIdempotencyKey ? 'disabled' : '' }}>
            Simpan Pembelian
        </button>
        <a href="{{ route('purchases.index') }}" class="btn btn-secondary">Batal</a>
    </div>
</form>

<script>
    let purchaseIndex = {{ $purchaseIndex ?? 1 }};

    function addPurchase() {
        const container = document.getElementById('purchase-container');
        const row = document.createElement('div');
        row.className = 'purchase-row';

        row.innerHTML = `
            <div class="form-group">
                <label for="purchase-ingredient-search-${purchaseIndex}">Cari Bahan</label>
                <div class="autocomplete"
                    data-autocomplete
                    data-autocomplete-url="{{ route('ingredients.autocomplete') }}"
                    data-autocomplete-target="purchase-ingredient-${purchaseIndex}"
                    data-autocomplete-meta="unit"
                    data-autocomplete-active-only="true">
                    <input id="purchase-ingredient-search-${purchaseIndex}" type="search"
                        class="autocomplete-input" data-autocomplete-input
                        autocomplete="off" aria-autocomplete="list" aria-expanded="false"
                        aria-controls="purchase-ingredient-suggestions-${purchaseIndex}"
                        placeholder="Ketik nama bahan...">
                    <div id="purchase-ingredient-suggestions-${purchaseIndex}"
                        class="autocomplete-suggestions" role="listbox"
                        aria-label="Saran bahan baku" hidden></div>
                </div>
                <label for="purchase-ingredient-${purchaseIndex}">Bahan Baku</label>
                <select id="purchase-ingredient-${purchaseIndex}" name="details[${purchaseIndex}][ingredient_id]" required>
                    <option value="">-- Pilih Bahan --</option>
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
@include('components.autocomplete')
@endsection

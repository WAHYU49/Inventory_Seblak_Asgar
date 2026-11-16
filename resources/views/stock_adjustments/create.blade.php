@extends('layouts.app')

@section('title', 'Tambah Penyesuaian Stok - Inventory Seblak Asgar')

@section('content')
<div class="page-header">
    <h1>Tambah Penyesuaian Stok</h1>
    <p>Catat penambahan atau pengurangan stok bahan baku.</p>
</div>
<p><a href="{{ route('stock-adjustments.index') }}" class="btn btn-secondary">Kembali</a></p>

<div class="content-card">
    @if ($ingredients->isEmpty())
        <div class="alert alert-info" role="status">
            <strong>Belum ada bahan baku.</strong>
            <p>Tambahkan bahan baku terlebih dahulu. Penyesuaian belum dapat disimpan tanpa bahan baku.</p>
            <a href="{{ route('ingredients.create') }}" class="btn btn-primary">Tambah Bahan Baku</a>
        </div>
    @endif
    @php
        $hasIdempotencyKey = isset($idempotencyKey)
            && is_string($idempotencyKey)
            && $idempotencyKey !== '';
    @endphp
    <form action="{{ route('stock-adjustments.store') }}" method="POST">
        @csrf
        @if ($hasIdempotencyKey)
            <input type="hidden" name="idempotency_key" value="{{ $idempotencyKey }}">
        @else
            <div class="alert alert-danger" role="alert">
                Token transaksi tidak tersedia. Muat ulang halaman sebelum menyimpan penyesuaian.
            </div>
        @endif

        @error('idempotency_key')
            <div class="alert alert-danger" role="alert">{{ $message }}</div>
        @enderror

        <div class="form-group">
            <label for="adjustment_date">Tanggal Penyesuaian</label>
            <input
                type="date"
                name="adjustment_date"
                id="adjustment_date"
                value="{{ old('adjustment_date', now('Asia/Jakarta')->toDateString()) }}"
                required
                @error('adjustment_date') aria-invalid="true" aria-describedby="adjustment-date-error" @enderror
            >
            @error('adjustment_date')
                <small id="adjustment-date-error" class="field-error">{{ $message }}</small>
            @enderror
        </div>

        <div class="form-group">
            <label for="ingredient_id">Bahan Baku</label>
            <select name="ingredient_id" id="ingredient_id" required
                {{ $ingredients->isEmpty() ? 'disabled' : '' }}
                @error('ingredient_id') aria-invalid="true" aria-describedby="ingredient-id-error" @enderror>
                <option value="">-- Pilih Bahan Baku --</option>

                @foreach($ingredients as $ingredient)
                    <option
                        value="{{ $ingredient->id }}"
                        data-stock="{{ \App\Support\InventoryNumberFormatter::format($ingredient->current_stock) }}"
                        data-unit="{{ $ingredient->unit }}"
                        {{ old('ingredient_id') == $ingredient->id ? 'selected' : '' }}
                    >
                        {{ $ingredient->code }} - {{ $ingredient->name }}
                    </option>
                @endforeach
            </select>
            @error('ingredient_id')
                <small id="ingredient-id-error" class="field-error">{{ $message }}</small>
            @enderror

            <small id="current-stock" class="field-help" aria-live="polite">
                Pilih bahan baku untuk melihat stok saat ini.
            </small>
        </div>

        <div class="form-group">
            <label for="type">Jenis Penyesuaian</label>
            <select name="type" id="type" required
                @error('type') aria-invalid="true" aria-describedby="adjustment-type-error" @enderror>
                <option value="">-- Pilih Jenis --</option>
                <option value="increase" {{ old('type') == 'increase' ? 'selected' : '' }}>
                    Penambahan Stok
                </option>
                <option value="decrease" {{ old('type') == 'decrease' ? 'selected' : '' }}>
                    Pengurangan Stok
                </option>
            </select>
            @error('type')
                <small id="adjustment-type-error" class="field-error">{{ $message }}</small>
            @enderror
        </div>

        <div class="form-group">
            <label for="quantity">Jumlah Penyesuaian</label>
            <input
                type="number"
                name="quantity"
                id="quantity"
                step="0.001"
                min="0.001"
                value="{{ old('quantity') }}"
                placeholder="Contoh: 5"
                required
                @error('quantity') aria-invalid="true" aria-describedby="adjustment-quantity-error" @enderror
            >
            @error('quantity')
                <small id="adjustment-quantity-error" class="field-error">{{ $message }}</small>
            @enderror
        </div>

        <div class="form-group">
            <label for="reason">Alasan Penyesuaian</label>
            <select name="reason" id="reason" required
                @error('reason') aria-invalid="true" aria-describedby="adjustment-reason-error" @enderror>
                <option value="">-- Pilih Alasan --</option>
                @foreach([
                    'Stok opname',
                    'Barang rusak',
                    'Barang kedaluwarsa',
                    'Kesalahan pencatatan',
                    'Kehilangan bahan baku',
                    'Lainnya'
                ] as $reason)
                    <option value="{{ $reason }}" {{ old('reason') == $reason ? 'selected' : '' }}>
                        {{ $reason }}
                    </option>
                @endforeach
            </select>
            @error('reason')
                <small id="adjustment-reason-error" class="field-error">{{ $message }}</small>
            @enderror
        </div>

        <div class="form-group">
            <label for="notes">Catatan Tambahan</label>
            <textarea
                name="notes"
                id="notes"
                rows="3"
                placeholder="Tambahkan keterangan jika diperlukan..."
                @error('notes') aria-invalid="true" aria-describedby="adjustment-notes-error" @enderror
            >{{ old('notes') }}</textarea>
            @error('notes')
                <small id="adjustment-notes-error" class="field-error">{{ $message }}</small>
            @enderror
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary"
                {{ $ingredients->isEmpty() || !$hasIdempotencyKey ? 'disabled' : '' }}>
                Simpan Penyesuaian
            </button>

            <a href="{{ route('stock-adjustments.index') }}" class="btn btn-secondary">
                Batal
            </a>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const ingredientSelect = document.getElementById('ingredient_id');
    const currentStock = document.getElementById('current-stock');

    if (!ingredientSelect || !currentStock) {
        return;
    }

    function updateStockInfo() {
        const selected = ingredientSelect.options[ingredientSelect.selectedIndex];

        if (!selected || !selected.value) {
            currentStock.textContent = 'Pilih bahan baku untuk melihat stok saat ini.';
            return;
        }

        const stock = selected.dataset.stock;
        const unit = selected.dataset.unit;

        currentStock.textContent = 'Stok saat ini: ' + stock + ' ' + unit;
    }

    ingredientSelect.addEventListener('change', updateStockInfo);
    updateStockInfo();
});
</script>
@endsection
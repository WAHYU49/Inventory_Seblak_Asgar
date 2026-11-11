@extends('layouts.app')

@section('content')
<div class="page-header">
    <div>
        <h2>Tambah Penyesuaian Stok</h2>
        <p>Catat penambahan atau pengurangan stok bahan baku.</p>
    </div>

    <a href="{{ route('stock-adjustments.index') }}" class="btn btn-secondary">
        Kembali
    </a>
</div>

<div class="content-card">
    <form action="{{ route('stock-adjustments.store') }}" method="POST">
        @csrf

        <div class="form-group">
            <label for="adjustment_date">Tanggal Penyesuaian</label>
            <input
                type="date"
                name="adjustment_date"
                id="adjustment_date"
                value="{{ old('adjustment_date', now('Asia/Jakarta')->toDateString()) }}"
                required
            >
            @error('adjustment_date')
                <small class="text-danger">{{ $message }}</small>
            @enderror
        </div>

        <div class="form-group">
            <label for="ingredient_id">Bahan Baku</label>
            <select name="ingredient_id" id="ingredient_id" required>
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
                <small class="text-danger">{{ $message }}</small>
            @enderror

            <small id="current-stock" style="display:block; margin-top:8px; color:#666;">
                Pilih bahan baku untuk melihat stok saat ini.
            </small>
        </div>

        <div class="form-group">
            <label for="type">Jenis Penyesuaian</label>
            <select name="type" id="type" required>
                <option value="">-- Pilih Jenis --</option>
                <option value="increase" {{ old('type') == 'increase' ? 'selected' : '' }}>
                    Penambahan Stok
                </option>
                <option value="decrease" {{ old('type') == 'decrease' ? 'selected' : '' }}>
                    Pengurangan Stok
                </option>
            </select>
            @error('type')
                <small class="text-danger">{{ $message }}</small>
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
            >
            @error('quantity')
                <small class="text-danger">{{ $message }}</small>
            @enderror
        </div>

        <div class="form-group">
            <label for="reason">Alasan Penyesuaian</label>
            <select name="reason" id="reason" required>
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
                <small class="text-danger">{{ $message }}</small>
            @enderror
        </div>

        <div class="form-group">
            <label for="notes">Catatan Tambahan</label>
            <textarea
                name="notes"
                id="notes"
                rows="3"
                placeholder="Tambahkan keterangan jika diperlukan..."
            >{{ old('notes') }}</textarea>
            @error('notes')
                <small class="text-danger">{{ $message }}</small>
            @enderror
        </div>

        <div style="margin-top: 20px;">
            <button type="submit" class="btn btn-primary">
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
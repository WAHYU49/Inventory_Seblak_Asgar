@extends('layouts.app')

@section('title', 'Tambah Pembelian - Inventory Seblak Asgar')

@section('content')

<div class="page-header">
    <h1>Tambah Pembelian Bahan Baku</h1>
    <p>Catat pembelian bahan baku untuk menambah persediaan inventory.</p>
</div>

<p>
    <a href="{{ route('purchases.index') }}">← Kembali ke Pembelian</a>
</p>

@if ($errors->any())
    <div style="background:#fde8e8; color:#a52828; padding:12px;
        border-radius:6px; margin-bottom:15px;">
        @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

<form action="{{ route('purchases.store') }}" method="POST">
    @csrf

    <div style="background:white; padding:20px; border-radius:10px;
        margin-bottom:20px; box-shadow:0 2px 8px rgba(0,0,0,0.05);">

        <h3>Informasi Pembelian</h3>

        <div style="margin-bottom:15px;">
            <label>Tanggal Pembelian</label><br>
            <input type="date" name="purchase_date"
                value="{{ old('purchase_date', date('Y-m-d')) }}" required>
        </div>

        <div style="margin-bottom:15px;">
            <label>Catatan</label><br>
            <textarea name="notes" rows="3"
                placeholder="Contoh: Pembelian bahan baku dari supplier">{{ old('notes') }}</textarea>
        </div>
    </div>

    <div style="background:white; padding:20px; border-radius:10px;
        margin-bottom:20px; box-shadow:0 2px 8px rgba(0,0,0,0.05);">

        <h3>Detail Bahan Baku</h3>

        <div id="purchase-container">

            <div class="purchase-row"
                style="display:grid; grid-template-columns:2fr 1fr auto;
                gap:10px; margin-bottom:15px; align-items:end;">

                <div>
                    <label>Bahan Baku</label>
                    <select name="details[0][ingredient_id]" required>
                        <option value="">-- Pilih Bahan --</option>
                        @foreach ($ingredients as $ingredient)
                            <option value="{{ $ingredient->id }}">
                                {{ $ingredient->name }} ({{ $ingredient->unit }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label>Jumlah</label>
                    <input type="number" name="details[0][quantity]"
                        min="0.001" step="0.001" placeholder="Jumlah" required>
                </div>

                <button type="button" onclick="removePurchase(this)">
                    Hapus
                </button>
            </div>

        </div>

        <button type="button" onclick="addPurchase()">
            + Tambah Bahan Baku
        </button>

    </div>

    <button type="submit"
        style="background:#183b2b; color:white; padding:12px 20px;
        border:none; border-radius:6px; cursor:pointer;">
        Simpan Pembelian
    </button>

</form>

<script>
    let purchaseIndex = 1;

    function addPurchase() {
        const container = document.getElementById('purchase-container');
        const row = document.createElement('div');

        row.className = 'purchase-row';
        row.style.cssText = `
            display:grid;
            grid-template-columns:2fr 1fr auto;
            gap:10px;
            margin-bottom:15px;
            align-items:end;
        `;

        row.innerHTML = `
            <div>
                <label>Bahan Baku</label>
                <select name="details[${purchaseIndex}][ingredient_id]" required>
                    <option value="">-- Pilih Bahan --</option>
                    @foreach ($ingredients as $ingredient)
                        <option value="{{ $ingredient->id }}">
                            {{ $ingredient->name }} ({{ $ingredient->unit }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label>Jumlah</label>
                <input type="number"
                    name="details[${purchaseIndex}][quantity]"
                    min="0.001" step="0.001"
                    placeholder="Jumlah" required>
            </div>

            <button type="button" onclick="removePurchase(this)">
                Hapus
            </button>
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
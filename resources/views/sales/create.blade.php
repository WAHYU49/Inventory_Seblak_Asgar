@extends('layouts.app')

@section('content')
<style>
    .sale-form-container {
        max-width: 1100px;
        margin: 0 auto;
    }

    .sale-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: 22px;
        margin-bottom: 20px;
    }

    .sale-card h3 {
        margin: 0 0 18px;
        color: #1f2937;
        font-size: 18px;
    }

    .sale-form-group {
        margin-bottom: 16px;
    }

    .sale-form-group label {
        display: block;
        margin-bottom: 7px;
        font-weight: 600;
        color: #374151;
    }

    .sale-form-group input,
    .sale-form-group select,
    .sale-form-group textarea {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        box-sizing: border-box;
    }

    .sale-form-group input:focus,
    .sale-form-group select:focus,
    .sale-form-group textarea:focus {
        outline: none;
        border-color: #15803d;
    }

    .product-row {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 16px;
        margin-bottom: 15px;
        background: #fafafa;
    }

    .product-row-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }

    .product-row-header strong {
        color: #374151;
    }

    .product-grid {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr;
        gap: 15px;
    }

    .product-price {
        background: #f3f4f6;
        color: #374151;
    }

    .recipe-info {
        margin-top: 12px;
        padding: 12px;
        border-radius: 6px;
        background: #f0fdf4;
        font-size: 13px;
        color: #166534;
    }

    .recipe-info ul {
        padding-left: 20px;
        margin: 8px 0 0;
    }

    .recipe-info li {
        margin-bottom: 4px;
    }

    .sale-summary {
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        border-radius: 8px;
        padding: 18px;
        margin-bottom: 20px;
    }

    .sale-summary h3 {
        margin: 0 0 10px;
        color: #166534;
    }

    .sale-total {
        font-size: 24px;
        font-weight: 700;
        color: #15803d;
    }

    .sale-button {
        display: inline-block;
        padding: 11px 18px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 600;
        text-decoration: none;
    }

    .sale-button-primary {
        background: #15803d;
        color: white;
    }

    .sale-button-primary:hover {
        background: #166534;
    }

    .sale-button-secondary {
        background: #e5e7eb;
        color: #374151;
    }

    .sale-button-danger {
        background: #fee2e2;
        color: #b91c1c;
        padding: 7px 10px;
    }

    .sale-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .sale-alert {
        padding: 13px 16px;
        border-radius: 6px;
        margin-bottom: 18px;
    }

    .sale-alert-error {
        background: #fef2f2;
        color: #b91c1c;
        border: 1px solid #fecaca;
    }

    .sale-alert-warning {
        background: #fffbeb;
        color: #92400e;
        border: 1px solid #fde68a;
    }

    .sale-empty {
        padding: 20px;
        background: #fffbeb;
        border: 1px solid #fde68a;
        border-radius: 8px;
        color: #92400e;
    }

    @media (max-width: 700px) {
        .product-grid {
            grid-template-columns: 1fr;
            gap: 0;
        }

        .sale-card {
            padding: 15px;
        }
    }
</style>

<div class="sale-form-container">

    <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:20px;">
        <div>
            <h2 style="margin:0 0 5px; color:#1f2937;">Tambah Penjualan</h2>
            <p style="margin:0; color:#6b7280; font-size:14px;">
                Catat transaksi dan kurangi stok bahan baku secara otomatis.
            </p>
        </div>

        <a href="{{ route('sales.index') }}"
           class="sale-button sale-button-secondary">
            Kembali
        </a>
    </div>

    @if (session('success'))
        <div class="sale-alert" style="background:#f0fdf4; color:#166534; border:1px solid #bbf7d0;">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="sale-alert sale-alert-error">
            <strong>Terjadi kesalahan:</strong>
            <ul style="margin:8px 0 0; padding-left:20px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($products->isEmpty())
        <div class="sale-empty">
            <strong>Belum ada menu yang bisa dijual.</strong>
            <p style="margin-bottom:0;">
                Pastikan produk sudah aktif dan memiliki resep aktif dengan bahan baku yang tersedia.
            </p>
        </div>
    @else

        <form action="{{ route('sales.store') }}" method="POST" id="saleForm">
            @csrf

            <div class="sale-card">
                <h3>Informasi Penjualan</h3>

                <div class="sale-form-group">
                    <label for="sale_date">Tanggal Penjualan</label>
                    <input
                        type="date"
                        id="sale_date"
                        name="sale_date"
                        value="{{ old('sale_date', date('Y-m-d')) }}"
                        required
                    >
                </div>

                <div class="sale-form-group">
                    <label for="notes">Catatan (Opsional)</label>
                    <textarea
                        id="notes"
                        name="notes"
                        rows="3"
                        placeholder="Masukkan catatan transaksi..."
                    >{{ old('notes') }}</textarea>
                </div>
            </div>

            <div class="sale-card">
                <h3>Daftar Menu</h3>

                <div id="productContainer"></div>

                <button
                    type="button"
                    id="addProduct"
                    class="sale-button sale-button-secondary"
                >
                    + Tambah Menu
                </button>
            </div>

            <div class="sale-summary">
                <h3>Ringkasan Penjualan</h3>

                <div style="color:#374151; margin-bottom:6px;">
                    Total Transaksi
                </div>

                <div class="sale-total" id="grandTotal">
                    Rp 0
                </div>

                <div id="ingredientSummary" style="margin-top:15px; color:#374151;">
                    Belum ada menu dipilih.
                </div>
            </div>

            <div class="sale-actions">
                <button
                    type="submit"
                    class="sale-button sale-button-primary"
                    id="submitSale"
                >
                    Simpan Penjualan
                </button>

                <a href="{{ route('sales.index') }}"
                   class="sale-button sale-button-secondary">
                    Batal
                </a>
            </div>
        </form>
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    // Data produk dan resep dari SaleController.
    const products = @json($productData);

    const container = document.getElementById('productContainer');
    const addButton = document.getElementById('addProduct');
    const grandTotal = document.getElementById('grandTotal');
    const ingredientSummary = document.getElementById('ingredientSummary');

    if (!container || !addButton) {
        return;
    }

    let rowIndex = 0;

    // Format angka menjadi Rupiah.
    function formatRupiah(value) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            maximumFractionDigits: 0
        }).format(value || 0);
    }

    // Membuat elemen HTML dengan aman.
    function createElement(tag, className, text) {
        const element = document.createElement(tag);

        if (className) {
            element.className = className;
        }

        if (text !== undefined) {
            element.textContent = text;
        }

        return element;
    }

    // Memperbarui pilihan agar satu menu tidak dipilih dua kali.
    function updateProductOptions() {
        const selectedValues = Array.from(
            container.querySelectorAll('.product-select')
        )
            .map(select => select.value)
            .filter(value => value !== '');

        container.querySelectorAll('.product-select').forEach(select => {
            Array.from(select.options).forEach(option => {
                if (option.value === '') {
                    return;
                }

                option.disabled =
                    selectedValues.includes(option.value) &&
                    select.value !== option.value;
            });
        });
    }

    // Membuat daftar bahan baku berdasarkan resep.
    function renderRecipe(row, product) {
        const recipeInfo = row.querySelector('.recipe-info');
        recipeInfo.replaceChildren();

        if (!product) {
            recipeInfo.textContent = 'Pilih menu untuk melihat kebutuhan bahan baku.';
            return;
        }

        const heading = createElement(
            'strong',
            '',
            'Kebutuhan bahan baku per porsi:'
        );

        const list = createElement('ul');

        product.recipe.forEach(item => {
            const text = item.name
                + ': '
                + item.quantity
                + ' '
                + item.unit
                + ' (stok tersedia: '
                + item.stock
                + ' '
                + item.unit
                + ')';

            list.appendChild(createElement('li', '', text));
        });

        recipeInfo.appendChild(heading);
        recipeInfo.appendChild(list);
    }

    // Menghitung total transaksi dan kebutuhan bahan baku.
    function updateSummary() {
        let total = 0;
        const requirements = {};

        container.querySelectorAll('.product-row').forEach(row => {
            const select = row.querySelector('.product-select');
            const quantityInput = row.querySelector('.product-quantity');
            const priceInput = row.querySelector('.product-price');

            const product = products.find(
                item => String(item.id) === select.value
            );

            if (!product) {
                return;
            }

            const quantity = Math.max(
                0,
                Number(quantityInput.value) || 0
            );

            const subtotal = product.price * quantity;

            priceInput.value = formatRupiah(subtotal);
            total += subtotal;

            product.recipe.forEach(item => {
                if (!requirements[item.name]) {
                    requirements[item.name] = {
                        quantity: 0,
                        unit: item.unit,
                        stock: item.stock
                    };
                }

                requirements[item.name].quantity +=
                    item.quantity * quantity;
            });
        });

        grandTotal.textContent = formatRupiah(total);

        ingredientSummary.replaceChildren();

        const heading = createElement(
            'strong',
            '',
            'Estimasi kebutuhan bahan baku:'
        );

        ingredientSummary.appendChild(heading);

        const list = createElement('ul');

        if (Object.keys(requirements).length === 0) {
            ingredientSummary.appendChild(
                createElement('p', '', 'Belum ada menu dipilih.')
            );
            return;
        }

        let hasInsufficientStock = false;

        Object.entries(requirements).forEach(([name, item]) => {
            const insufficient = item.quantity > item.stock;

            if (insufficient) {
                hasInsufficientStock = true;
            }

            const text = name
                + ': '
                + item.quantity.toFixed(3)
                + ' '
                + item.unit
                + ' / stok '
                + item.stock
                + ' '
                + item.unit
                + (insufficient ? ' - STOK TIDAK CUKUP' : '');

            const listItem = createElement('li', '', text);

            if (insufficient) {
                listItem.style.color = '#b91c1c';
                listItem.style.fontWeight = '600';
            }

            list.appendChild(listItem);
        });

        ingredientSummary.appendChild(list);

        if (hasInsufficientStock) {
            const warning = createElement(
                'p',
                '',
                'Perhatian: ada bahan baku yang stoknya tidak mencukupi.'
            );

            warning.style.color = '#b91c1c';
            warning.style.fontWeight = '600';

            ingredientSummary.appendChild(warning);
        }
    }

    // Membuat satu baris menu.
    function addProductRow() {
        const row = createElement('div', 'product-row');

        const header = createElement('div', 'product-row-header');
        const title = createElement('strong', '', 'Menu Penjualan');

        const removeButton = createElement(
            'button',
            'sale-button sale-button-danger',
            'Hapus'
        );

        removeButton.type = 'button';

        header.appendChild(title);
        header.appendChild(removeButton);

        const grid = createElement('div', 'product-grid');

        // Pilihan menu.
        const productGroup = createElement('div', 'sale-form-group');
        const productLabel = createElement('label', '', 'Pilih Menu');

        const select = createElement('select', 'product-select');
        select.name = 'details[' + rowIndex + '][product_id]';
        select.required = true;

        const defaultOption = createElement(
            'option',
            '',
            '-- Pilih Menu --'
        );

        defaultOption.value = '';
        select.appendChild(defaultOption);

        products.forEach(product => {
            const option = createElement(
                'option',
                '',
                product.name + ' - ' + formatRupiah(product.price)
            );

            option.value = product.id;
            select.appendChild(option);
        });

        productGroup.appendChild(productLabel);
        productGroup.appendChild(select);

        // Jumlah menu.
        const quantityGroup = createElement('div', 'sale-form-group');
        const quantityLabel = createElement('label', '', 'Jumlah Porsi');

        const quantityInput = createElement(
            'input',
            'product-quantity'
        );

        quantityInput.type = 'number';
        quantityInput.name = 'details[' + rowIndex + '][quantity]';
        quantityInput.min = '1';
        quantityInput.step = '1';
        quantityInput.value = '1';
        quantityInput.required = true;

        quantityGroup.appendChild(quantityLabel);
        quantityGroup.appendChild(quantityInput);

        // Harga subtotal.
        const priceGroup = createElement('div', 'sale-form-group');
        const priceLabel = createElement('label', '', 'Subtotal');

        const priceInput = createElement(
            'input',
            'product-price'
        );

        priceInput.type = 'text';
        priceInput.value = formatRupiah(0);
        priceInput.readOnly = true;

        priceGroup.appendChild(priceLabel);
        priceGroup.appendChild(priceInput);

        grid.appendChild(productGroup);
        grid.appendChild(quantityGroup);
        grid.appendChild(priceGroup);

        const recipeInfo = createElement(
            'div',
            'recipe-info',
            'Pilih menu untuk melihat kebutuhan bahan baku.'
        );

        row.appendChild(header);
        row.appendChild(grid);
        row.appendChild(recipeInfo);

        container.appendChild(row);

        rowIndex++;

        select.addEventListener('change', function () {
            const product = products.find(
                item => String(item.id) === select.value
            );

            renderRecipe(row, product);
            updateProductOptions();
            updateSummary();
        });

        quantityInput.addEventListener('input', function () {
            updateSummary();
        });

        removeButton.addEventListener('click', function () {
            row.remove();
            updateProductOptions();
            updateSummary();

            if (container.querySelectorAll('.product-row').length === 0) {
                addProductRow();
            }
        });

        updateProductOptions();
    }

    // Tombol tambah menu.
    addButton.addEventListener('click', function () {
        const selectedValues = Array.from(
            container.querySelectorAll('.product-select')
        )
            .map(select => select.value)
            .filter(value => value !== '');

        const availableProduct = products.find(
            product => !selectedValues.includes(String(product.id))
        );

        if (!availableProduct) {
            alert('Semua menu yang tersedia sudah ditambahkan.');
            return;
        }

        addProductRow();
    });

    // Validasi sebelum formulir dikirim.
    document.getElementById('saleForm').addEventListener(
        'submit',
        function (event) {
            const rows = container.querySelectorAll('.product-row');

            if (rows.length === 0) {
                event.preventDefault();
                alert('Tambahkan minimal satu menu.');
                return;
            }

            const selectedValues = Array.from(
                container.querySelectorAll('.product-select')
            ).map(select => select.value);

            if (selectedValues.some(value => value === '')) {
                event.preventDefault();
                alert('Silakan pilih semua menu terlebih dahulu.');
                return;
            }

            if (new Set(selectedValues).size !== selectedValues.length) {
                event.preventDefault();
                alert('Menu yang sama tidak boleh dipilih lebih dari satu kali.');
            }
        }
    );

    // Baris pertama otomatis dibuat ketika halaman dibuka.
    addProductRow();

});
</script>
@endsection

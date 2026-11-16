@extends('layouts.app')

@section('title', 'Tambah Penjualan - Inventory Seblak Asgar')

@section('content')
<style>
    .sale-form-container {
        max-width: 1100px;
        margin: 0 auto;
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
        grid-template-columns: 2fr 1fr;
        gap: 15px;
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

    @media (max-width: 700px) {
        .product-grid {
            grid-template-columns: 1fr;
            gap: 0;
        }
    }
</style>

<div class="sale-form-container">

    <div class="page-header">
        <h1>Tambah Penjualan</h1>
        <p>Catat transaksi dan kurangi stok bahan baku secara otomatis.</p>
    </div>

    <p><a href="{{ route('sales.index') }}" class="btn btn-secondary">Kembali</a></p>

    @if (session('success'))
        <div class="alert alert-success" role="status">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger" role="alert" aria-live="assertive">
            <strong>Periksa kembali isian berikut:</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($products->isEmpty())
        <div class="alert alert-warning" role="status">
            <strong>Belum ada menu yang bisa dijual.</strong>
            <p>
                Pastikan produk sudah aktif dan memiliki resep aktif dengan bahan baku yang tersedia.
            </p>
            <div class="form-actions">
                <a href="{{ route('products.index') }}" class="btn btn-secondary">Kelola Produk</a>
                <a href="{{ route('recipes.index') }}" class="btn btn-secondary">Kelola Resep</a>
            </div>
        </div>
    @else

        <form action="{{ route('sales.store') }}" method="POST" id="saleForm">
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
                    Token transaksi tidak tersedia. Muat ulang halaman sebelum menyimpan penjualan.
                </div>
            @endif
            <div class="content-card">
                <h3>Informasi Penjualan</h3>

                <div class="form-group">
                    <label for="sale_date">Tanggal Penjualan</label>
                    <input
                        type="date"
                        id="sale_date"
                        name="sale_date"
                        value="{{ old('sale_date', now('Asia/Jakarta')->toDateString()) }}"
                        required
                        @error('sale_date') aria-invalid="true" aria-describedby="sale-date-error" @enderror
                    >
                    @error('sale_date') <small id="sale-date-error" class="field-error">{{ $message }}</small> @enderror
                </div>

                <div class="form-group">
                    <label for="notes">Catatan (Opsional)</label>
                    <textarea
                        id="notes"
                        name="notes"
                        rows="3"
                        placeholder="Masukkan catatan transaksi..."
                        @error('notes') aria-invalid="true" aria-describedby="sale-notes-error" @enderror
                    >{{ old('notes') }}</textarea>
                    @error('notes') <small id="sale-notes-error" class="field-error">{{ $message }}</small> @enderror
                </div>
            </div>

            <div class="content-card">
                <h3>Daftar Menu</h3>

                <div id="productContainer"></div>

                <button
                    type="button"
                    id="addProduct"
                    class="btn btn-secondary"
                >
                    + Tambah Menu
                </button>
            </div>

            <div class="sale-summary">
                <h3>Ringkasan Kebutuhan Stok</h3>

                <div id="ingredientSummary" aria-live="polite">
                    Belum ada menu dipilih.
                </div>
            </div>

            <div class="form-actions">
                <button
                    type="submit"
                    class="btn btn-primary"
                    id="submitSale"
                    {{ $hasIdempotencyKey ? '' : 'disabled' }}
                >
                    Simpan Penjualan
                </button>

                <a href="{{ route('sales.index') }}"
                   class="btn btn-secondary">
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
    const oldDetails = @json(old('details', []));
    const validationErrors = @json($errors->getMessages());

    const container = document.getElementById('productContainer');
    const addButton = document.getElementById('addProduct');
    const ingredientSummary = document.getElementById('ingredientSummary');

    if (!container || !addButton) {
        return;
    }

    let rowIndex = 0;
    const formatQuantity = value => new Intl.NumberFormat('id-ID', {
        maximumFractionDigits: 3
    }).format(value);

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

    function attachFieldError(field, group, errorKey) {
        const messages = validationErrors[errorKey];

        if (!messages || messages.length === 0) {
            return;
        }

        const error = createElement('small', 'field-error', messages[0]);
        error.id = field.id + '-error';
        field.setAttribute('aria-invalid', 'true');
        field.setAttribute('aria-describedby', error.id);
        group.appendChild(error);
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
                + formatQuantity(item.quantity)
                + ' '
                + item.unit
                + ' (stok tersedia: '
                + formatQuantity(item.stock)
                + ' '
                + item.unit
                + ')';

            list.appendChild(createElement('li', '', text));
        });

        recipeInfo.appendChild(heading);
        recipeInfo.appendChild(list);
    }

    // Menghitung kebutuhan bahan baku.
    function updateSummary() {
        const requirements = {};

        container.querySelectorAll('.product-row').forEach(row => {
            const select = row.querySelector('.product-select');
            const quantityInput = row.querySelector('.product-quantity');

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
                + formatQuantity(item.quantity)
                + ' '
                + item.unit
                + ' / stok '
                + formatQuantity(item.stock)
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
    function addProductRow(detail = {}, index = rowIndex) {
        detail = detail || {};
        const row = createElement('div', 'product-row');

        const header = createElement('div', 'product-row-header');
        const title = createElement('strong', '', 'Menu Penjualan');

        const removeButton = createElement('button', 'btn btn-danger', 'Hapus');

        removeButton.type = 'button';

        header.appendChild(title);
        header.appendChild(removeButton);

        const grid = createElement('div', 'product-grid');

        // Pilihan menu.
        const productGroup = createElement('div', 'form-group');
        const productLabel = createElement('label', '', 'Pilih Menu');

        const select = createElement('select', 'product-select');
        select.id = 'sale-product-' + index;
        select.name = 'details[' + index + '][product_id]';
        select.required = true;
        productLabel.htmlFor = select.id;

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
                product.name
            );

            option.value = product.id;
            select.appendChild(option);
        });
        select.value = detail.product_id ? String(detail.product_id) : '';

        productGroup.appendChild(productLabel);
        productGroup.appendChild(select);
        attachFieldError(select, productGroup, 'details.' + index + '.product_id');

        // Jumlah menu.
        const quantityGroup = createElement('div', 'form-group');
        const quantityLabel = createElement('label', '', 'Jumlah Porsi');

        const quantityInput = createElement(
            'input',
            'product-quantity'
        );

        quantityInput.id = 'sale-quantity-' + index;
        quantityInput.type = 'number';
        quantityInput.name = 'details[' + index + '][quantity]';
        quantityInput.min = '1';
        quantityInput.step = '1';
        quantityInput.value = detail.quantity !== undefined && detail.quantity !== null
            ? detail.quantity
            : '1';
        quantityInput.required = true;
        quantityLabel.htmlFor = quantityInput.id;

        quantityGroup.appendChild(quantityLabel);
        quantityGroup.appendChild(quantityInput);
        attachFieldError(quantityInput, quantityGroup, 'details.' + index + '.quantity');

        grid.appendChild(productGroup);
        grid.appendChild(quantityGroup);

        const recipeInfo = createElement(
            'div',
            'recipe-info',
            'Pilih menu untuk melihat kebutuhan bahan baku.'
        );

        row.appendChild(header);
        row.appendChild(grid);
        row.appendChild(recipeInfo);

        container.appendChild(row);

        const numericIndex = Number(index);
        if (Number.isInteger(numericIndex)) {
            rowIndex = Math.max(rowIndex, numericIndex + 1);
        }

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

        renderRecipe(row, products.find(item => String(item.id) === select.value));
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

    const previousRows = Object.entries(oldDetails);

    if (previousRows.length === 0) {
        addProductRow();
    } else {
        previousRows.forEach(([index, detail]) => addProductRow(detail, index));
    }

    updateSummary();

});
</script>
@endsection

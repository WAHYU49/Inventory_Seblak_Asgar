<button
    type="button"
    class="sidebar-overlay"
    aria-label="Tutup navigasi"
    aria-hidden="true"
    tabindex="-1"
></button>

<aside class="sidebar" id="app-sidebar" aria-labelledby="sidebar-brand">

    <div class="sidebar-brand">
        <span id="sidebar-brand">INVENTORY<br>SEBLAK ASGAR</span>
        <button type="button" class="sidebar-close" aria-label="Tutup navigasi" aria-controls="app-sidebar">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>

    <div class="sidebar-title">Menu Utama</div>

    <a href="{{ url('/') }}"
       class="{{ request()->is('/') ? 'active' : '' }}"
       @if (request()->is('/')) aria-current="page" @endif>
        Dashboard
    </a>

    <div class="sidebar-title">Master Data</div>

    <a href="{{ route('categories.index') }}"
       class="{{ request()->routeIs('categories.*') ? 'active' : '' }}"
       @if (request()->routeIs('categories.*')) aria-current="page" @endif>
        Kategori Menu
    </a>

    <a href="{{ route('products.index') }}"
       class="{{ request()->routeIs('products.*') ? 'active' : '' }}"
       @if (request()->routeIs('products.*')) aria-current="page" @endif>
        Produk Menu
    </a>

    <a href="{{ route('ingredients.index') }}"
       class="{{ request()->routeIs('ingredients.*') ? 'active' : '' }}"
       @if (request()->routeIs('ingredients.*')) aria-current="page" @endif>
        Bahan Baku
    </a>

    <a href="{{ route('recipes.index') }}"
       class="{{ request()->routeIs('recipes.*') ? 'active' : '' }}"
       @if (request()->routeIs('recipes.*')) aria-current="page" @endif>
        Resep Menu
    </a>

    <div class="sidebar-title">Transaksi</div>

    <a href="{{ route('purchases.index') }}"
       class="{{ request()->routeIs('purchases.*') ? 'active' : '' }}"
       @if (request()->routeIs('purchases.*')) aria-current="page" @endif>
        Pembelian Bahan Baku
    </a>

    <a href="{{ route('sales.index') }}"
       class="{{ request()->routeIs('sales.*') ? 'active' : '' }}"
       @if (request()->routeIs('sales.*')) aria-current="page" @endif>
        Penjualan
    </a>

    <div class="sidebar-title">Laporan</div>

    <a href="{{ route('stock-adjustments.index') }}"
       class="{{ request()->routeIs('stock-adjustments.*') ? 'active' : '' }}"
       @if (request()->routeIs('stock-adjustments.*')) aria-current="page" @endif>
        Penyesuaian Stok
    </a>

    <a href="{{ route('stock-movements.index') }}"
       class="{{ request()->routeIs('stock-movements.*') ? 'active' : '' }}"
       @if (request()->routeIs('stock-movements.*')) aria-current="page" @endif>
        Riwayat Stok
    </a>

    <div class="sidebar-footer">

        <div class="admin-info">
            Login sebagai:<br>
            <strong>{{ Auth::user()->name }}</strong><br>
            <small>Administrator</small>
        </div>

        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="logout-button">
                Logout
            </button>
        </form>

    </div>

</aside>
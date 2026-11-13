<button
    type="button"
    class="sidebar-overlay"
    aria-label="Tutup navigasi"
    aria-hidden="true"
    tabindex="-1"
></button>

<aside class="sidebar" id="app-sidebar" aria-label="Navigasi utama">

    <div class="sidebar-brand">
        <span>INVENTORY<br>SEBLAK ASGAR</span>
        <button type="button" class="sidebar-close" aria-label="Tutup navigasi">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>

    <div class="sidebar-title">Menu Utama</div>

    <a href="{{ url('/') }}"
       class="{{ request()->is('/') ? 'active' : '' }}">
        Dashboard
    </a>

    <div class="sidebar-title">Master Data</div>

    <a href="{{ route('categories.index') }}"
       class="{{ request()->routeIs('categories.*') ? 'active' : '' }}">
        Kategori Menu
    </a>

    <a href="{{ route('products.index') }}"
       class="{{ request()->routeIs('products.*') ? 'active' : '' }}">
        Produk Menu
    </a>

    <a href="{{ route('ingredients.index') }}"
       class="{{ request()->routeIs('ingredients.*') ? 'active' : '' }}">
        Bahan Baku
    </a>

    <a href="{{ route('recipes.index') }}"
       class="{{ request()->routeIs('recipes.*') ? 'active' : '' }}">
        Resep Menu
    </a>

    <div class="sidebar-title">Transaksi</div>

    <a href="{{ route('purchases.index') }}"
       class="{{ request()->routeIs('purchases.*') ? 'active' : '' }}">
        Pembelian Bahan Baku
    </a>

    <a href="{{ route('sales.index') }}"
    class="{{ request()->routeIs('sales.*') ? 'active' : '' }}">
        Penjualan
    </a>

    <div class="sidebar-title">Laporan</div>

    <a href="{{ route('stock-adjustments.index') }}"
       class="{{ request()->routeIs('stock-adjustments.*') ? 'active' : '' }}">
        Penyesuaian Stok
    </a>

    <a href="{{ route('stock-movements.index') }}"
       class="{{ request()->routeIs('stock-movements.*') ? 'active' : '' }}">
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
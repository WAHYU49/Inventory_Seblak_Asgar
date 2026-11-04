
<style>
    * {
        box-sizing: border-box;
    }

    body {
        margin: 0;
        font-family: Arial, sans-serif;
        background: #f4f6f5;
    }

    .sidebar {
        width: 250px;
        height: 100vh;
        background: #183b2b;
        color: white;
        position: fixed;
        top: 0;
        left: 0;
        padding: 25px 15px;
        overflow-y: auto;
    }

    .sidebar-brand {
        font-size: 19px;
        font-weight: bold;
        padding: 0 12px 25px;
        border-bottom: 1px solid #41614e;
        margin-bottom: 25px;
        line-height: 1.5;
    }

    .sidebar-title {
        font-size: 11px;
        color: #a8c0b0;
        margin: 20px 12px 10px;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .sidebar a {
        display: block;
        color: #dce8df;
        text-decoration: none;
        padding: 12px 15px;
        margin-bottom: 5px;
        border-radius: 6px;
        font-size: 14px;
        transition: 0.2s;
    }

    .sidebar a:hover {
        background: #28513b;
        color: white;
    }

    .sidebar a.active {
        background: #3d7956;
        color: white;
        font-weight: bold;
    }

    .main-content {
        margin-left: 250px;
        padding: 30px;
        min-height: 100vh;
    }

    @media (max-width: 768px) {
        .sidebar {
            width: 190px;
        }

        .main-content {
            margin-left: 190px;
            padding: 15px;
        }
    }
</style>


<aside class="sidebar">

    <div class="sidebar-brand">
        INVENTORY<br>
        SEBLAK ASGAR
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

    <a href="#" onclick="alert('Fitur penjualan belum dibuat.'); return false;">
        Penjualan
    </a>

    <div class="sidebar-title">Laporan</div>

    <a href="#" onclick="alert('Fitur laporan belum dibuat.'); return false;">
        Laporan Stok
    </a>

    <a href="#" onclick="alert('Fitur laporan belum dibuat.'); return false;">
        Riwayat Stok
    </a>

</aside>




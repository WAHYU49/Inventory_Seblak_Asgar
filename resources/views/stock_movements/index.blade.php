
@extends('layouts.app')

@section('content')

<div style="padding: 20px;">

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div>
            <h2 style="margin: 0;">Riwayat Pergerakan Stok</h2>
            <p style="color: #777; margin-top: 5px;">
                Catatan seluruh perubahan stok bahan baku.
            </p>
        </div>
    </div>

    {{-- Filter dan pencarian --}}
    <div style="background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; border: 1px solid #eee;">

        <form action="{{ route('stock-movements.index') }}" method="GET">

            <div style="display: flex; flex-wrap: wrap; gap: 12px; align-items: end;">

                <div style="flex: 2; min-width: 180px;">
                    <label for="movement-search">Cari Bahan Baku</label>
                    <input
                        id="movement-search"
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Nama atau kode bahan..."
                        style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; margin-top: 5px;"
                    >
                </div>

                <div style="flex: 1; min-width: 140px;">
                    <label for="movement-type">Jenis Transaksi</label>
                    <select
                        id="movement-type"
                        name="type"
                        style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; margin-top: 5px;"
                    >
                        <option value="">Semua Transaksi</option>
                        <option value="purchase" {{ request('type') == 'purchase' ? 'selected' : '' }}>Pembelian</option>
                        <option value="sale" {{ request('type') == 'sale' ? 'selected' : '' }}>Penjualan</option>
                        <option value="adjustment" {{ request('type') == 'adjustment' ? 'selected' : '' }}>Penyesuaian</option>
                        <option value="initial" {{ request('type') == 'initial' ? 'selected' : '' }}>Stok Awal</option>
                    </select>
                </div>

                <div style="flex: 1; min-width: 140px;">
                    <label for="movement-start-date">Dari Tanggal</label>
                    <input
                        id="movement-start-date"
                        type="date"
                        name="start_date"
                        value="{{ request('start_date') }}"
                        style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; margin-top: 5px;"
                    >
                </div>

                <div style="flex: 1; min-width: 140px;">
                    <label for="movement-end-date">Sampai Tanggal</label>
                    <input
                        id="movement-end-date"
                        type="date"
                        name="end_date"
                        value="{{ request('end_date') }}"
                        style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; margin-top: 5px;"
                    >
                </div>

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Cari
                </button>

                <a
                    href="{{ route('stock-movements.index') }}"
                    class="btn btn-secondary"
                >
                    Reset
                </a>

            </div>
        </form>
    </div>

    {{-- Tabel riwayat --}}
    <div style="background: white; border-radius: 10px; border: 1px solid #eee; overflow-x: auto;">

        <table style="width: 100%; border-collapse: collapse; min-width: 1000px;">

            <thead style="background: #f5f7f5;">
                <tr>
                    <th style="padding: 13px; text-align: left;">No</th>
                    <th style="padding: 13px; text-align: left;">Waktu (WIB)</th>
                    <th style="padding: 13px; text-align: left;">Bahan Baku</th>
                    <th style="padding: 13px; text-align: left;">Jenis</th>
                    <th style="padding: 13px; text-align: right;">Jumlah</th>
                    <th style="padding: 13px; text-align: right;">Stok Sebelum</th>
                    <th style="padding: 13px; text-align: right;">Stok Sesudah</th>
                    <th style="padding: 13px; text-align: left;">No. Referensi</th>
                    <th style="padding: 13px; text-align: left;">Keterangan</th>
                </tr>
            </thead>

            <tbody>

                @forelse ($movements as $movement)

                    <tr style="border-top: 1px solid #eee;">

                        <td style="padding: 13px;">
                            {{ $movements->firstItem() + $loop->index }}
                        </td>

                        <td style="padding: 13px;">
                            {{ \App\Support\WibDateTimeFormatter::format($movement->created_at) }}
                        </td>

                        <td style="padding: 13px;">
                            <strong>{{ $movement->ingredient->name ?? 'Bahan tidak ditemukan' }}</strong>
                            <br>
                            <small style="color: #777;">
                                {{ $movement->ingredient->code ?? '-' }}
                            </small>
                        </td>

                        <td style="padding: 13px;">
                            @if ($movement->type === 'purchase')
                                <span class="badge badge-success">
                                    Pembelian
                                </span>
                            @elseif ($movement->type === 'sale')
                                <span class="badge badge-danger">
                                    Penjualan
                                </span>
                            @elseif ($movement->type === 'initial')
                                <span class="badge badge-info">
                                    Stok Awal
                                </span>
                            @else
                                <span class="badge badge-warning">
                                    Penyesuaian
                                </span>
                            @endif
                        </td>

                        <td style="padding: 13px; text-align: right;">
                            {{ \App\Support\InventoryNumberFormatter::format($movement->quantity) }}
                            {{ $movement->ingredient->unit ?? '' }}
                        </td>

                        <td style="padding: 13px; text-align: right;">
                            {{ \App\Support\InventoryNumberFormatter::format($movement->stock_before) }}
                        </td>

                        <td style="padding: 13px; text-align: right;">
                            {{ \App\Support\InventoryNumberFormatter::format($movement->stock_after) }}
                        </td>

                        <td style="padding: 13px;">
                            {{ $movement->reference_number ?? '-' }}
                        </td>

                        <td style="padding: 13px;">
                            {{ $movement->notes ?? '-' }}
                        </td>

                    </tr>

                @empty

                    <tr>
                        <td colspan="9" style="padding: 30px; text-align: center; color: #777;">
                            Belum ada riwayat pergerakan stok.
                        </td>
                    </tr>

                @endforelse

            </tbody>

        </table>
    </div>

    {{-- Pagination --}}
    <div style="margin-top: 20px;">
        {{ $movements->links() }}
    </div>

</div>

@endsection

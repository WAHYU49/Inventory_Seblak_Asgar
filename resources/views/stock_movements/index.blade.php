
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
                    <label>Cari Bahan Baku</label>
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Nama atau kode bahan..."
                        style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; margin-top: 5px;"
                    >
                </div>

                <div style="flex: 1; min-width: 140px;">
                    <label>Jenis Transaksi</label>
                    <select
                        name="type"
                        style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; margin-top: 5px;"
                    >
                        <option value="">Semua Transaksi</option>
                        <option value="purchase" {{ request('type') == 'purchase' ? 'selected' : '' }}>Pembelian</option>
                        <option value="sale" {{ request('type') == 'sale' ? 'selected' : '' }}>Penjualan</option>
                        <option value="adjustment" {{ request('type') == 'adjustment' ? 'selected' : '' }}>Penyesuaian</option>
                    </select>
                </div>

                <div style="flex: 1; min-width: 140px;">
                    <label>Dari Tanggal</label>
                    <input
                        type="date"
                        name="start_date"
                        value="{{ request('start_date') }}"
                        style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; margin-top: 5px;"
                    >
                </div>

                <div style="flex: 1; min-width: 140px;">
                    <label>Sampai Tanggal</label>
                    <input
                        type="date"
                        name="end_date"
                        value="{{ request('end_date') }}"
                        style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; margin-top: 5px;"
                    >
                </div>

                <button
                    type="submit"
                    style="background: #166534; color: white; border: none; padding: 11px 18px; border-radius: 5px; cursor: pointer;"
                >
                    Cari
                </button>

                <a
                    href="{{ route('stock-movements.index') }}"
                    style="background: #eee; color: #333; padding: 11px 18px; border-radius: 5px; text-decoration: none;"
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
                    <th style="padding: 13px; text-align: left;">Tanggal</th>
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
                            {{ $movement->created_at->format('d-m-Y H:i') }}
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
                                <span style="background: #dcfce7; color: #166534; padding: 5px 9px; border-radius: 5px;">
                                    Pembelian
                                </span>
                            @elseif ($movement->type === 'sale')
                                <span style="background: #fee2e2; color: #991b1b; padding: 5px 9px; border-radius: 5px;">
                                    Penjualan
                                </span>
                            @else
                                <span style="background: #fef3c7; color: #92400e; padding: 5px 9px; border-radius: 5px;">
                                    Penyesuaian
                                </span>
                            @endif
                        </td>

                        <td style="padding: 13px; text-align: right;">
                            {{ number_format((float) $movement->quantity, 3, ',', '.') }}
                            {{ $movement->ingredient->unit ?? '' }}
                        </td>

                        <td style="padding: 13px; text-align: right;">
                            {{ number_format((float) $movement->stock_before, 3, ',', '.') }}
                        </td>

                        <td style="padding: 13px; text-align: right;">
                            {{ number_format((float) $movement->stock_after, 3, ',', '.') }}
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

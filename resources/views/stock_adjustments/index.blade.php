@extends('layouts.app')

@section('title', 'Penyesuaian Stok - Inventory Seblak Asgar')

@section('content')
<div class="page-header">
    <h1>Penyesuaian Stok</h1>
    <p>Kelola penambahan dan pengurangan stok bahan baku.</p>
</div>
<p><a href="{{ route('stock-adjustments.create') }}" class="btn btn-primary">+ Tambah Penyesuaian</a></p>

@if(session('success'))
    <div class="alert alert-success" role="status">
        {{ session('success') }}
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger" role="alert">
        @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

<div class="content-card">
    <form action="{{ route('stock-adjustments.index') }}" method="GET"
        class="filter-form">
        <div class="filter-field">
            <label for="adjustment-start-date">Dari Tanggal</label>
            <input id="adjustment-start-date" type="date" name="start_date" value="{{ $startDate }}">
        </div>
        <div class="filter-field">
            <label for="adjustment-end-date">Sampai Tanggal</label>
            <input id="adjustment-end-date" type="date" name="end_date" value="{{ $endDate }}">
        </div>
        <button type="submit" class="btn btn-primary">Filter</button>
        <a href="{{ route('stock-adjustments.index') }}" class="btn btn-secondary">Reset</a>
    </form>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th class="table-number">No.</th>
                    <th>Tanggal</th>
                    <th>Waktu Input (WIB)</th>
                    <th>No. Penyesuaian</th>
                    <th>Bahan Baku</th>
                    <th>Jenis</th>
                    <th class="table-number">Jumlah</th>
                    <th class="table-number">Stok Sebelum</th>
                    <th class="table-number">Stok Sesudah</th>
                    <th>Alasan</th>
                </tr>
            </thead>

            <tbody>
                @forelse($adjustments as $adjustment)
                    <tr>
                        <td class="table-number">{{ $loop->iteration + ($adjustments->currentPage() - 1) * $adjustments->perPage() }}</td>

                        <td>
                            {{ $adjustment->adjustment_date->format('d-m-Y') }}
                        </td>

                        <td>
                            {{ \App\Support\WibDateTimeFormatter::format($adjustment->created_at) }}
                        </td>

                        <td>
                            {{ $adjustment->adjustment_number }}
                        </td>

                        <td>
                            {{ $adjustment->ingredient->name }}
                        </td>

                        <td>
                            @if($adjustment->type === 'increase')
                                <span class="badge badge-success">Penambahan</span>
                            @else
                                <span class="badge badge-danger">Pengurangan</span>
                            @endif
                        </td>

                        <td class="table-number">
                            {{ \App\Support\InventoryNumberFormatter::format($adjustment->quantity) }}
                            {{ $adjustment->ingredient->unit }}
                        </td>

                        <td class="table-number">
                            {{ \App\Support\InventoryNumberFormatter::format($adjustment->stock_before) }}
                        </td>

                        <td class="table-number">
                            {{ \App\Support\InventoryNumberFormatter::format($adjustment->stock_after) }}
                        </td>

                        <td>
                            {{ $adjustment->reason }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10">
                            Belum ada data penyesuaian stok.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $adjustments->links() }}
</div>
@endsection
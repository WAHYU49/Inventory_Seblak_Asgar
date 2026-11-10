@extends('layouts.app')

@section('content')
<div class="page-header">
    <div>
        <h2>Penyesuaian Stok</h2>
        <p>Kelola penambahan dan pengurangan stok bahan baku.</p>
    </div>

    <a href="{{ route('stock-adjustments.create') }}" class="btn btn-primary">
        + Tambah Penyesuaian
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger">
        @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

<div class="content-card">
    <form action="{{ route('stock-adjustments.index') }}" method="GET"
        style="display:flex; flex-wrap:wrap; gap:12px; align-items:end; margin-bottom:20px;">
        <div class="form-group">
            <label for="adjustment-start-date">Dari Tanggal</label>
            <input id="adjustment-start-date" type="date" name="start_date" value="{{ $startDate }}">
        </div>
        <div class="form-group">
            <label for="adjustment-end-date">Sampai Tanggal</label>
            <input id="adjustment-end-date" type="date" name="end_date" value="{{ $endDate }}">
        </div>
        <button type="submit" class="btn btn-primary">Filter</button>
        <a href="{{ route('stock-adjustments.index') }}" class="btn btn-secondary">Reset</a>
    </form>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Tanggal</th>
                    <th>Tanggal Input</th>
                    <th>No. Penyesuaian</th>
                    <th>Bahan Baku</th>
                    <th>Jenis</th>
                    <th>Jumlah</th>
                    <th>Stok Sebelum</th>
                    <th>Stok Sesudah</th>
                    <th>Alasan</th>
                </tr>
            </thead>

            <tbody>
                @forelse($adjustments as $adjustment)
                    <tr>
                        <td>{{ $loop->iteration + ($adjustments->currentPage() - 1) * $adjustments->perPage() }}</td>

                        <td>
                            {{ $adjustment->adjustment_date->format('d/m/Y') }}
                        </td>

                        <td>
                            {{ $adjustment->created_at->format('d/m/Y H:i') }}
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

                        <td>
                            {{ \App\Support\InventoryNumberFormatter::format($adjustment->quantity) }}
                            {{ $adjustment->ingredient->unit }}
                        </td>

                        <td>
                            {{ \App\Support\InventoryNumberFormatter::format($adjustment->stock_before) }}
                        </td>

                        <td>
                            {{ \App\Support\InventoryNumberFormatter::format($adjustment->stock_after) }}
                        </td>

                        <td>
                            {{ $adjustment->reason }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" style="text-align: center; padding: 25px;">
                            Belum ada data penyesuaian stok.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top: 20px;">
        {{ $adjustments->links() }}
    </div>
</div>
@endsection
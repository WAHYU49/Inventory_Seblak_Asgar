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

<div class="content-card">
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
                            {{ number_format($adjustment->quantity, 3, ',', '.') }}
                            {{ $adjustment->ingredient->unit }}
                        </td>

                        <td>
                            {{ number_format($adjustment->stock_before, 3, ',', '.') }}
                        </td>

                        <td>
                            {{ number_format($adjustment->stock_after, 3, ',', '.') }}
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
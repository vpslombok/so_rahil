@extends('layouts.app')

@section('title', 'Laporan Selisih Stok Final')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Laporan Selisih Stok (Finalisasi)</h5>
                </div>
                <div class="card-body">
                    @if($stockAudits->isEmpty())
                    <div class="alert alert-info text-center">
                        Belum ada data stok opname yang difinalisasi.
                    </div>
                    @else
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover table-striped">
                            <thead class="table-light">
                                <tr>
                                    <th>No.</th>
                                    <th>Event SO</th>
                                    <th>Produk (SKU)</th>
                                    <th>Stok Awal Sistem</th>
                                    <th>Stok Fisik Dihitung</th>
                                    <th>Selisih</th>
                                    <th>Dinalisasi Oleh</th>
                                    <th>Tanggal Finalisasi</th>
                                    <th>Catatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($stockAudits as $index => $audit)
                                @if($audit->difference != 0) {{-- Tambahkan kondisi ini --}}
                                <tr>
                                    <td>{{ $stockAudits->firstItem() + $index }}</td>
                                    <td>{{ $audit->stockOpnameEvent->name ?? 'N/A' }}</td>
                                    <td>
                                        {{ $audit->product->name ?? 'N/A' }}
                                        @if($audit->product && $audit->product->sku)
                                        <small class="d-block text-muted">({{ $audit->product->sku }})</small>
                                        @endif
                                    </td>
                                    <td class="text-end">{{ number_format($audit->initial_stock, 0, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format($audit->counted_stock, 0, ',', '.') }}</td>
                                    <td class="text-end fw-bold {{ $audit->difference > 0 ? 'text-success' : ($audit->difference < 0 ? 'text-danger' : '') }}">
                                        {{ number_format($audit->difference, 0, ',', '.') }}
                                    </td>
                                    <td>{{ $audit->user->username ?? 'N/A' }}</td>
                                    <td>{{ $audit->created_at ? \Carbon\Carbon::parse($audit->created_at)->isoFormat('D MMM YYYY, HH:mm') : '-' }}</td>
                                    <td>{{ $audit->notes ?? '-' }}</td>
                                </tr>
                                @endif {{-- Tutup kondisi --}}
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if($stockAudits->hasPages())
                    <div class="mt-3 d-flex justify-content-center">
                        {{ $stockAudits->links() }}
                    </div>
                    @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .table th,
    .table td {
        vertical-align: middle;
    }
</style>
@endpush
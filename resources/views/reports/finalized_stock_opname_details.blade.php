@extends('layouts.app')

@section('title', 'Laporan Selisih Stok Final')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-11 col-xl-10">
            <div class="card shadow rounded-4 border-0">
                <div class="card-header bg-gradient-primary text-white rounded-top-4 d-flex align-items-center justify-content-between">
                    <h4 class="mb-0"><i class="fas fa-clipboard-check me-2"></i>Laporan Selisih Stok <span class="badge bg-light text-primary">Finalisasi</span></h4>
                </div>
                <div class="card-body bg-light rounded-bottom-4">
                    @if($stockAudits->isEmpty())
                    <div class="d-flex flex-column align-items-center justify-content-center py-5">
                        <i class="fas fa-box-open fa-3x text-secondary mb-3"></i>
                        <h5 class="text-secondary mb-2">Belum ada data stok opname yang difinalisasi.</h5>
                        <p class="text-muted">Silakan lakukan finalisasi SO untuk melihat laporan di sini.</p>
                    </div>
                    @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle bg-white rounded shadow-sm">
                            <thead class="thead-light">
                                <tr>
                                    <th class="text-center">No.</th>
                                    <th>Event SO</th>
                                    <th>Produk (SKU)</th>
                                    <th class="text-end">Stok Sistem</th>
                                    <th class="text-end">Stok Fisik</th>
                                    <th class="text-end">Selisih</th>
                                    <th>Finalisasi Oleh</th>
                                    <th>Tanggal Finalisasi</th>
                                    <th>Catatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($stockAudits as $index => $audit)
                                @if($audit->difference != 0)
                                <tr>
                                    <td class="text-center">{{ $stockAudits->firstItem() + $index }}</td>
                                    <td><span class="badge bg-info text-dark">{{ $audit->stockOpnameEvent->name ?? 'N/A' }}</span></td>
                                    <td>
                                        <span class="fw-bold">{{ $audit->product->name ?? 'N/A' }}</span>
                                        @if($audit->product && $audit->product->sku)
                                        <small class="d-block text-muted">({{ $audit->product->sku }})</small>
                                        @endif
                                    </td>
                                    <td class="text-end">{{ number_format($audit->initial_stock, 0, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format($audit->counted_stock, 0, ',', '.') }}</td>
                                    <td class="text-end">
                                        <span class="fw-bold badge {{ $audit->difference > 0 ? 'bg-success' : ($audit->difference < 0 ? 'bg-danger' : 'bg-secondary') }}">
                                            {{ number_format($audit->difference, 0, ',', '.') }}
                                        </span>
                                    </td>
                                    <td><span class="badge bg-secondary">{{ $audit->user->username ?? 'N/A' }}</span></td>
                                    <td><i class="far fa-calendar-check text-success"></i> <span class="ms-1">{{ $audit->created_at ? \Carbon\Carbon::parse($audit->created_at)->isoFormat('D MMM YYYY, HH:mm') : '-' }}</span></td>
                                    <td>{{ $audit->notes ?? '-' }}</td>
                                </tr>
                                @endif
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
    .card-header.bg-gradient-primary {
        background: linear-gradient(90deg, #007bff 0%, #0056b3 100%) !important;
    }

    .table th,
    .table td {
        vertical-align: middle;
    }

    .table thead th {
        background-color: #f8f9fa;
        font-weight: 600;
    }

    .badge {
        font-size: 0.95em;
        padding: 0.5em 0.8em;
        border-radius: 0.7em;
    }
</style>
@endpush
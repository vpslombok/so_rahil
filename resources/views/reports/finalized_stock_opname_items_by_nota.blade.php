@extends('layouts.app')

@section('title', 'Detail Laporan Selisih Stok Nota: ' . $nomor_nota)

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10 col-xl-9">
            <div class="card shadow rounded-4 border-0">
                <div class="card-header bg-gradient-primary text-white rounded-top-4 d-flex align-items-center justify-content-between">
                    <h4 class="mb-0"><i class="fas fa-receipt me-2"></i>Detail Selisih Stok untuk Nota: <span class="badge bg-light text-primary">{{ $nomor_nota }}</span></h4>
                    <a href="{{ route('stock_audit_report.summary') }}" class="btn btn-light btn-sm">
                        <i class="fas fa-arrow-left"></i> Kembali ke Ringkasan
                    </a>
                </div>
                <div class="card-body bg-light rounded-bottom-4">
                    @if($stockAuditDetails->isEmpty())
                    <div class="d-flex flex-column align-items-center justify-content-center py-5">
                        <i class="fas fa-box-open fa-3x text-secondary mb-3"></i>
                        <h5 class="text-secondary mb-2">Tidak ada detail item yang ditemukan untuk nomor nota ini atau Anda tidak memiliki akses.</h5>
                    </div>
                    @else
                    <div class="mb-3">
                        <span class="me-4"><strong>Event SO:</strong> <span class="badge bg-info text-dark">{{ $stockAuditDetails->first()->stockOpnameEvent->name ?? 'N/A' }}</span></span>
                        <span class="me-4"><strong>Finalisasi Oleh:</strong> <span class="badge bg-secondary">{{ $stockAuditDetails->first()->user->username ?? 'N/A' }}</span></span>
                        <span><strong>Tanggal Finalisasi:</strong> <i class="far fa-calendar-check text-success"></i> <span class="ms-1">{{ $stockAuditDetails->first()->checked_at ? \Carbon\Carbon::parse($stockAuditDetails->first()->checked_at)->isoFormat('D MMMM YYYY, HH:mm') : '-' }}</span></span>
                        <span class="me-4"><strong>Alasan:</strong> <span class="badge bg-warning text-dark">{{ $stockAuditDetails->first()->notes ?? 'N/A' }}</span></span>
                    </div>
                    @if($stockAuditDetails->isNotEmpty())
                    @php
                    $allNotes = $stockAuditDetails->pluck('notes')->filter()->unique()->values();
                    @endphp
                    @if($allNotes->isNotEmpty())
                    <div class="alert alert-info mb-3">
                        <strong>Catatan Finalisasi:</strong>
                        <ul class="mb-0 ps-3">
                            @foreach($allNotes as $note)
                            <li>{{ $note }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                    @endif
                    <div class="table-responsive">
                        <table class="table table-hover align-middle bg-white rounded shadow-sm">
                            <thead class="thead-light">
                                <tr>
                                    <th>Kode</th>
                                    <th>Barcode</th>
                                    <th>Produk (SKU)</th>
                                    <th class="text-end">Stok Sistem</th>
                                    <th class="text-end">Stok Fisik</th>
                                    <th class="text-end">Selisih</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($stockAuditDetails as $index => $detail)
                                @if($detail->difference != 0)
                                <tr>
                                    <td>{{ $detail->product->product_code ?? '-' }}</td>
                                    <td>{{ $detail->product->barcode ?? '-' }}</td>
                                    <td>
                                        <span class="fw-bold">{{ $detail->product->name ?? 'N/A' }}</span>
                                        @if($detail->product && $detail->product->sku)
                                        <small class="d-block text-muted">({{ $detail->product->sku }})</small>
                                        @endif
                                    </td>
                                    <td class="text-end">{{ number_format($detail->system_stock, 0, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format($detail->physical_stock, 0, ',', '.') }}</td>
                                    <td class="text-end">
                                        <span class="fw-bold badge {{ $detail->difference > 0 ? 'bg-success' : ($detail->difference < 0 ? 'bg-danger' : 'bg-secondary') }}">
                                            {{ number_format($detail->difference, 0, ',', '.') }}
                                        </span>
                                    </td>
                                </tr>
                                @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="row justify-content-end mt-3">
                        <div class="col-md-5 col-lg-4">
                            <div class="alert alert-secondary text-end mb-0">
                                <strong>Total Keseluruhan Selisih:</strong>
                                <span class="fw-bold {{ $totalDifference > 0 ? 'text-success' : ($totalDifference < 0 ? 'text-danger' : 'text-dark') }}">
                                    {{ number_format($totalDifference, 0, ',', '.') }}
                                </span>
                            </div>
                        </div>
                    </div>
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
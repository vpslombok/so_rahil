@extends('layouts.app')

@section('title', 'Detail Laporan Selisih Stok Nota: ' . $nomor_nota)

@section('content')
<div class="container-fluid py-3 px-1 px-md-3">
    <div class="row justify-content-center">
        <div class="col-12 col-md-11 col-lg-10">
            <div class="card shadow rounded-4 border-0">
                <div class="card-header bg-gradient-primary text-white rounded-top-4 d-flex align-items-center flex-wrap justify-content-between">
                    <h4 class="mb-0"><i class="fas fa-receipt me-2"></i>Detail Selisih Stok Nota: <span class="badge bg-light text-primary">{{ $nomor_nota }}</span></h4>
                    <a href="{{ route('stock_audit_report.summary') }}" class="btn btn-light btn-sm mt-2 mt-md-0">
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
                    <!-- HEADER INFO FINALISASI (ambil dari baris pertama)-->
                    @php $first = $stockAuditDetails->first(); @endphp
                    <div class="row mb-3 g-2">
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="small text-muted">Event SO</div>
                            <div class="fw-semibold">{{ $first->stockOpnameEvent->name ?? '-' }}</div>
                        </div>
                        <div class="col-6 col-md-3 col-lg-2">
                            <div class="small text-muted">Nomor Nota</div>
                            <div class="fw-semibold">{{ $first->nomor_nota ?? '-' }}</div>
                        </div>
                        <div class="col-6 col-md-3 col-lg-2">
                            <div class="small text-muted">Tanggal Finalisasi</div>
                            <div class="fw-semibold">{{ $first->checked_at ? \Carbon\Carbon::parse($first->checked_at)->isoFormat('D MMM YYYY, HH:mm') : '-' }}</div>
                        </div>
                        <div class="col-6 col-md-3 col-lg-2">
                            <div class="small text-muted">User</div>
                            <div class="fw-semibold">{{ $first->user->username ?? '-' }}</div>
                        </div>
                        <div class="col-6 col-md-3 col-lg-2">
                            <div class="small text-muted">Catatan</div>
                            <div class="fw-semibold">{{ $first->notes ?? '-' }}</div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle bg-white rounded shadow-sm mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Kode</th>
                                    <th>Barcode</th>
                                    <th>Produk</th>
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
                                    <td class="fw-semibold">{{ $detail->product->name ?? 'N/A' }}
                                        @if($detail->product && $detail->product->sku)
                                        <small class="d-block d-sm-none text-muted">({{ $detail->product->sku }})</small>
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
        word-break: break-word;
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

    .table-bordered th,
    .table-bordered td {
        border: 1px solid #dee2e6 !important;
    }

    @media (max-width: 575.98px) {

        .table th,
        .table td {
            font-size: 0.93em;
            padding: 0.4em 0.2em;
        }

        .container-fluid {
            padding-left: 0.2rem !important;
            padding-right: 0.2rem !important;
        }
    }
</style>
@endpush
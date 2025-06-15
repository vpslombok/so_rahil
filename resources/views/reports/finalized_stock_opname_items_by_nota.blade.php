@extends('layouts.app')

@section('title', 'Detail Laporan Selisih Stok Nota: ' . $nomor_nota)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Detail Selisih Stok untuk Nota: {{ $nomor_nota }}</h5>
                    <a href="{{ route('stock_audit_report.summary') }}" class="btn btn-light btn-sm">
                        <i class="fas fa-arrow-left"></i> Kembali ke Ringkasan
                    </a>
                </div>
                <div class="card-body">
                    @if($stockAuditDetails->isEmpty())
                        <div class="alert alert-warning text-center">
                            Tidak ada detail item yang ditemukan untuk nomor nota ini atau Anda tidak memiliki akses.
                        </div>
                    @else
                        <div class="mb-3">
                            <p><strong>Event SO:</strong> {{ $stockAuditDetails->first()->stockOpnameEvent->name ?? 'N/A' }}</p>
                            <p><strong>Dinalisasi Oleh:</strong> {{ $stockAuditDetails->first()->user->username ?? 'N/A' }}</p>
                            <p><strong>Tanggal Finalisasi (Item Pertama):</strong> {{ $stockAuditDetails->first()->checked_at ? \Carbon\Carbon::parse($stockAuditDetails->first()->checked_at)->isoFormat('D MMMM YYYY, HH:mm') : '-' }}</p>
                        </div>
                        <hr>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover table-striped">
                                <thead class="table-light">
                                    <tr>
                                        <th>No.</th>
                                        <th>Produk (SKU)</th>
                                        <th>Stok Sistem</th>
                                        <th>Stok Fisik</th>
                                        <th>Selisih</th>
                                        <th>Catatan</th>
                                        <th>Dicek Pada (Item)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($stockAuditDetails as $index => $detail)
                                    @if($detail->difference != 0) {{-- Tambahkan kondisi ini --}}
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>
                                            {{ $detail->product->name ?? 'N/A' }}
                                            @if($detail->product && $detail->product->sku)
                                                <small class="d-block text-muted">({{ $detail->product->sku }})</small>
                                            @endif
                                        </td>
                                        <td class="text-end">{{ number_format($detail->system_stock, 0, ',', '.') }}</td>
                                        <td class="text-end">{{ number_format($detail->physical_stock, 0, ',', '.') }}</td>
                                        <td class="text-end fw-bold {{ $detail->difference > 0 ? 'text-success' : ($detail->difference < 0 ? 'text-danger' : '') }}">
                                            {{ number_format($detail->difference, 0, ',', '.') }}
                                        </td>
                                        <td>{{ $detail->notes ?? '-' }}</td>
                                        <td>{{ $detail->checked_at ? \Carbon\Carbon::parse($detail->checked_at)->isoFormat('D MMM YYYY, HH:mm') : '-' }}</td>
                                    </tr>
                                    @endif {{-- Tutup kondisi --}}
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <hr>
                        <div class="row justify-content-end">
                            <div class="col-md-4">
                                <h5 class="text-end">Total Keseluruhan Selisih:
                                    <span class="fw-bold {{ $totalDifference > 0 ? 'text-success' : ($totalDifference < 0 ? 'text-danger' : 'text-dark') }}">
                                        {{ number_format($totalDifference, 0, ',', '.') }}
                                    </span>
                                </h5>
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
    .table th, .table td {
        vertical-align: middle;
    }
</style>
@endpush
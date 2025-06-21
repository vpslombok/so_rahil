@extends('layouts.app')

@section('title', 'Ringkasan Laporan Selisih Stok Final')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10 col-xl-9">
            <div class="card shadow rounded-4 border-0">
                <div class="card-header bg-gradient-primary text-white rounded-top-4 d-flex align-items-center justify-content-between">
                    <h4 class="mb-0"><i class="fas fa-clipboard-list mr-2"></i>Ringkasan Laporan Selisih Stok <span class="badge bg-light text-primary">Finalisasi per Nota</span></h4>
                </div>
                <div class="card-body bg-light rounded-bottom-4">
                    @if($finalizedGroups->isEmpty())
                    <div class="d-flex flex-column align-items-center justify-content-center py-5">
                        <i class="fas fa-box-open fa-3x text-secondary mb-3"></i>
                        <h5 class="text-secondary mb-2">Belum ada data stok opname yang difinalisasi.</h5>
                        <p class="text-muted">Silakan lakukan finalisasi SO untuk melihat ringkasan di sini.</p>
                    </div>
                    @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle bg-white rounded shadow-sm">
                            <thead class="thead-light">
                                <tr>
                                    <th class="text-center" style="width: 5%;">No.</th>
                                    <th>Nomor Nota</th>
                                    <th>Event SO</th>
                                    <th>Finalisasi Oleh</th>
                                    <th>Tanggal Finalisasi</th>
                                    <th class="text-center" style="width: 15%;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($finalizedGroups as $index => $group)
                                <tr>
                                    <td class="text-center">{{ $finalizedGroups->firstItem() + $index }}</td>
                                    <td>
                                        <span class="fw-bold text-primary">{{ $group->nomor_nota }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-info text-dark">{{ $group->stockOpnameEvent->name ?? 'N/A' }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">{{ $group->user->username ?? 'N/A' }}</span>
                                    </td>
                                    <td>
                                        <i class="far fa-calendar-check text-success"></i>
                                        <span class="ms-1">{{ $group->latest_checked_at ? \Carbon\Carbon::parse($group->latest_checked_at)->isoFormat('D MMM YYYY, HH:mm') : '-' }}</span>
                                    </td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" id="aksiDropdown{{ $group->nomor_nota }}" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="aksiDropdown{{ $group->nomor_nota }}">
                                                <li>
                                                    <a href="{{ route('stock_audit_report.details_by_nota', ['nomor_nota' => $group->nomor_nota]) }}" class="dropdown-item" title="Lihat Detail Item">
                                                        <i class="fas fa-eye text-primary me-2"></i> Detail
                                                    </a>
                                                </li>
                                                @if(Auth::user()->role === 'admin' || Auth::id() == $group->user_id)
                                                <li>
                                                    <form action="{{ route('stock_audit_report.destroy_group') }}" method="POST" onsubmit="return confirm('Anda yakin ingin menghapus data finalisasi SO ini? Tindakan ini tidak dapat dibatalkan.');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <input type="hidden" name="nomor_nota" value="{{ $group->nomor_nota }}">
                                                        <input type="hidden" name="stock_opname_event_id" value="{{ $group->stock_opname_event_id }}">
                                                        <input type="hidden" name="user_id_for_deletion" value="{{ $group->user_id }}">
                                                        <button type="submit" class="dropdown-item text-danger">
                                                            <i class="fas fa-trash me-2"></i> Hapus
                                                        </button>
                                                    </form>
                                                </li>
                                                @endif
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if($finalizedGroups->hasPages())
                    <div class="mt-3 d-flex justify-content-center">
                        {{ $finalizedGroups->links() }}
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

    .btn-outline-primary,
    .btn-outline-danger {
        border-radius: 50%;
        width: 36px;
        height: 36px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0;
    }

    .btn-outline-primary i,
    .btn-outline-danger i {
        font-size: 1.1rem;
    }

    .badge {
        font-size: 0.95em;
        padding: 0.5em 0.8em;
        border-radius: 0.7em;
    }
</style>
@endpush
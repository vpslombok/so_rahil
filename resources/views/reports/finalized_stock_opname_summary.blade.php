@extends('layouts.app')

@section('title', 'Ringkasan Laporan Selisih Stok Final')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Ringkasan Laporan Selisih Stok (Finalisasi per Nota)</h5>
                </div>
                <div class="card-body">
                    @if($finalizedGroups->isEmpty())
                    <div class="alert alert-info text-center">
                        Belum ada data stok opname yang difinalisasi.
                    </div>
                    @else
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover table-striped">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 5%;">No.</th>
                                    <th>Nomor Nota</th>
                                    <th>Event SO</th>
                                    <th>Dinalisasi Oleh</th>
                                    <th>Tanggal Finalisasi</th>
                                    <th style="width: 15%;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($finalizedGroups as $index => $group)
                                <tr>
                                    <td>{{ $finalizedGroups->firstItem() + $index }}</td>
                                    <td>{{ $group->nomor_nota }}</td>
                                    <td>{{ $group->stockOpnameEvent->name ?? 'N/A' }}</td>
                                    <td>{{ $group->user->username ?? 'N/A' }}</td>
                                    <td>{{ $group->latest_checked_at ? \Carbon\Carbon::parse($group->latest_checked_at)->isoFormat('D MMM YYYY, HH:mm') : '-' }}</td>
                                    <td>
                                        <a href="{{ route('stock_audit_report.details_by_nota', ['nomor_nota' => $group->nomor_nota]) }}" class="btn btn-sm btn-info" title="Lihat Detail Item">
                                            <i class="fas fa-eye"></i> Detail
                                        </a>
                                        @if(Auth::user()->role === 'admin' || Auth::id() == $group->user_id)
                                        <form action="{{ route('stock_audit_report.destroy_group') }}" method="POST" class="d-inline" onsubmit="return confirm('Anda yakin ingin menghapus data finalisasi SO ini? Tindakan ini tidak dapat dibatalkan.');">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="nomor_nota" value="{{ $group->nomor_nota }}">
                                            <input type="hidden" name="stock_opname_event_id" value="{{ $group->stock_opname_event_id }}">
                                            <input type="hidden" name="user_id_for_deletion" value="{{ $group->user_id }}">
                                            <button type="submit" class="btn btn-sm btn-danger" title="Hapus Data Finalisasi">
                                                <i class="fas fa-trash"></i> Hapus
                                            </button>
                                        </form>
                                        @endif
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
    .table th,
    .table td {
        vertical-align: middle;
    }
</style>
@endpush
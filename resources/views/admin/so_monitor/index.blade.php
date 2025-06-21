@extends('layouts.app')

@section('title', 'Monitor Stock Opname Aktif')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800">Monitor Stock Opname Aktif</h1>
    <p class="mb-4">Halaman ini menampilkan semua sesi Stock Opname, baik yang sedang berjalan (aktif) maupun yang sudah difinalisasi oleh pengguna.</p>

    @include('layouts.flash-messages')

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Daftar Sesi Stock Opname</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.so_monitor.index') }}" class="mb-3">
                <div class="row g-2">
                    <div class="col-md-3">
                        <input type="text" name="search_user" class="form-control form-control-sm" placeholder="Cari User..." value="{{ request('search_user') }}">
                    </div>
                    <div class="col-md-3">
                        <input type="text" name="search_nota" class="form-control form-control-sm" placeholder="Cari No. Nota..." value="{{ request('search_nota') }}">
                    </div>
                    <div class="col-md-3">
                        <input type="text" name="search_event" class="form-control form-control-sm" placeholder="Cari Event / 'SO Rak Umum'..." value="{{ request('search_event') }}">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary btn-sm w-100">Cari</button>
                    </div>
                </div>
                <div class="row g-2 mt-1">
                    <div class="col-md-3 offset-md-9">
                        <a href="{{ route('admin.so_monitor.index') }}" class="btn btn-secondary btn-sm w-100">Reset Filter</a>
                    </div>
                </div>
            </form>

            @if($sessions->isEmpty())
            <div class="alert alert-info text-center">
                Tidak ada sesi Stock Opname yang ditemukan sesuai filter pencarian.
            </div>
            @else
            <div class="table-responsive">
                <table class="table table-hover align-middle bg-white rounded shadow-sm">
                    <thead class="thead-light">
                        <tr>
                            <th class="text-center">No.</th>
                            <th>User</th>
                            <th>Nomor Nota</th>
                            <th>SO Event</th>
                            <th class="text-center">Waktu Mulai</th>
                            <th class="text-center">Aktivitas Terakhir</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sessions as $index => $session)
                        <tr>
                            <td class="text-center">{{ $sessions->firstItem() + $index }}</td>
                            <td>
                                @if($session->user)
                                <span class="fw-bold">{{ $session->user->name }}</span>
                                <span class="badge bg-secondary ms-1">{{ $session->user->username }}</span>
                                @else
                                <span class="text-muted">User Tidak Diketahui</span>
                                @endif
                            </td>
                            <td><span class="fw-bold text-primary">{{ $session->nomor_nota }}</span></td>
                            <td><span class="badge bg-info text-dark">{{ $session->stockOpnameEvent->name ?? 'SO Rak Umum' }}</span></td>
                            <td class="text-center">{{ \Carbon\Carbon::parse($session->session_start_time)->isoFormat('DD MMM YYYY, HH:mm') }}</td>
                            <td class="text-center">{{ \Carbon\Carbon::parse($session->last_activity_time)->isoFormat('DD MMM YYYY, HH:mm') }}</td>
                            <td class="text-center">
                                @if ($session->finalization_status === 'Finalized')
                                <span class="badge bg-success">Finalisasi</span>
                                @else
                                <span class="badge bg-warning text-dark">Aktif</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" id="aksiDropdown{{ $session->nomor_nota }}{{ $session->user_id }}" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="aksiDropdown{{ $session->nomor_nota }}{{ $session->user_id }}">
                                        <li>
                                            <a href="{{ route('stock_check.show_differences', ['nomor_nota' => $session->nomor_nota, 'user_id' => $session->user_id]) }}" class="dropdown-item" title="Lihat Detail Entri">
                                                <i class="bi bi-eye-fill text-primary me-2"></i> Detail Temp
                                            </a>
                                        </li>
                                        @if ($session->finalization_status !== 'Finalized')
                                        <li>
                                            <form action="{{ route('admin.so_monitor.destroy_session') }}" method="POST" onsubmit="return confirm('Anda yakin ingin menghapus sesi SO ini (Nota: {{ $session->nomor_nota }} untuk user {{ $session->user->name ?? $session->user_id }})? Semua entri sementara akan dihapus permanen.');">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="nomor_nota" value="{{ $session->nomor_nota }}">
                                                <input type="hidden" name="user_id" value="{{ $session->user_id }}">
                                                <input type="hidden" name="stock_opname_event_id" value="{{ $session->stock_opname_event_id }}">
                                                <button type="submit" class="dropdown-item text-danger">
                                                    <i class="bi bi-trash-fill me-2"></i> Hapus Temp
                                                </button>
                                            </form>
                                        </li>
                                        @else
                                        <li>
                                            <button class="dropdown-item text-muted" disabled>
                                                <i class="bi bi-trash-fill me-2"></i> Hapus Temp
                                            </button>
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
            @if($sessions->hasPages())
            <div class="d-flex justify-content-center mt-3">
                {{ $sessions->appends(request()->query())->links('vendor.pagination.custom') }}
            </div>
            @endif
            @endif
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

    .table thead th {
        background-color: #f8f9fa;
        font-weight: 600;
    }

    .badge {
        font-size: 0.95em;
        padding: 0.5em 0.8em;
        border-radius: 0.7em;
    }

    .btn-outline-primary {
        border-radius: 50%;
        width: 36px;
        height: 36px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0;
    }

    .btn-outline-primary i {
        font-size: 1.1rem;
    }
</style>
@endpush
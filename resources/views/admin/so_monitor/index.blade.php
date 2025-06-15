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
                <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>User</th>
                            <th>Nomor Nota</th>
                            <th>SO Event</th>
                            <th>Waktu Mulai Sesi</th>
                            <th>Aktivitas Terakhir</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sessions as $index => $session)
                        <tr>
                            <td>{{ $sessions->firstItem() + $index }}</td>
                            <td>
                                @if($session->user)
                                {{ $session->user->name }} ({{ $session->user->username }})
                                @else
                                <span class="text-muted">User Tidak Diketahui</span>
                                @endif
                            </td>
                            <td>{{ $session->nomor_nota }}</td>
                            <td>{{ $session->stockOpnameEvent->name ?? 'SO Rak Umum' }}</td>
                            <td>{{ \Carbon\Carbon::parse($session->session_start_time)->isoFormat('DD MMM YYYY, HH:mm') }}</td>
                            <td>{{ \Carbon\Carbon::parse($session->last_activity_time)->isoFormat('DD MMM YYYY, HH:mm') }}</td>
                            <td>
                                @if ($session->finalization_status === 'Finalized')
                                    <span class="badge bg-success">Finalisasi</span>
                                @else
                                    <span class="badge bg-warning text-dark">Aktif</span>
                                @endif
                            </td>
                            <td>
                                {{-- Tombol Detail selalu tampil, namun mungkin perlu penyesuaian di halaman detail jika data sudah final --}}
                                <a href="{{ route('stock_check.show_differences', ['nomor_nota' => $session->nomor_nota, 'user_id' => $session->user_id, 'stock_opname_event_id' => $session->stock_opname_event_id]) }}" class="btn btn-info btn-sm mb-1" title="Lihat Detail Entri">
                                    <i class="bi bi-eye-fill"></i> Detail Temp
                                </a>

                                @if ($session->finalization_status !== 'Finalized')
                                    <form action="{{ route('admin.so_monitor.destroy_session') }}" method="POST" class="d-inline" onsubmit="return confirm('Anda yakin ingin menghapus sesi SO ini (Nota: {{ $session->nomor_nota }} untuk user {{ $session->user->name ?? $session->user_id }})? Semua entri sementara akan dihapus permanen.');">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="nomor_nota" value="{{ $session->nomor_nota }}">
                                        <input type="hidden" name="user_id" value="{{ $session->user_id }}">
                                        <input type="hidden" name="stock_opname_event_id" value="{{ $session->stock_opname_event_id }}">
                                        <button type="submit" class="btn btn-danger btn-sm mb-1" title="Hapus Sesi SO (Hapus Entri Sementara)">
                                            <i class="bi bi-trash-fill"></i> Hapus Temp
                                        </button>
                                    </form>
                                @else
                                    <button class="btn btn-secondary btn-sm mb-1" disabled title="Sesi sudah difinalisasi, entri sementara tidak dapat dihapus dari sini.">
                                        <i class="bi bi-trash-fill"></i> Hapus Temp
                                    </button>
                                @endif
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
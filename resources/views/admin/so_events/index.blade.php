@extends('layouts.app')

@section('title', 'Manajemen SO Item Rahil')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Manajemen SO Item Rahil</h1>
        <a href="{{ route('admin.so-events.create') }}" class="btn btn-primary btn-sm shadow-sm" title="Buat SO Item Rahil Baru">
            <i class="bi bi-plus-circle fa-sm text-white-50"></i> Buat SO Item Rahil Baru
        </a>
    </div>

    @include('layouts.flash-messages')

    {{-- Form Pencarian Event --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('admin.so-events.index') }}" class="row gx-2 gy-2 align-items-center">
                <div class="col-md-10">
                    <label for="search_event_name" class="visually-hidden">Cari Nama SO Item Rahil</label>
                    <input type="text" name="search_event_name" id="search_event_name" class="form-control form-control-sm" placeholder="Ketik nama SO Item Rahil untuk mencari..." value="{{ $searchEventName ?? '' }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm btn-primary w-100">
                        <i class="bi bi-search"></i> Cari
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Daftar SO Item Rahil</h6>
        </div>
        @if($soEvents->isNotEmpty())
        <div class="card-header bg-white border-bottom-0 pt-3 pb-0">
            <form method="GET" action="{{ route('admin.so-events.index') }}" id="perPageFormSoEvents" class="d-flex align-items-center justify-content-start">
                <input type="hidden" name="search_event_name" value="{{ $searchEventName ?? '' }}">
                <label for="per_page_select_so" class="form-label me-2 mb-0">Tampilkan:</label>
                <select name="per_page" id="per_page_select_so" class="form-select form-select-sm" style="width: auto;" onchange="document.getElementById('perPageFormSoEvents').submit();">
                    <option value="5" {{ ($perPage ?? 5) == 5 ? 'selected' : '' }}>5</option>
                    <option value="25" {{ ($perPage ?? 5) == 25 ? 'selected' : '' }}>25</option>
                    <option value="50" {{ ($perPage ?? 5) == 50 ? 'selected' : '' }}>50</option>
                    <option value="100" {{ ($perPage ?? 5) == 100 ? 'selected' : '' }}>100</option>
                </select>
                <span class="ms-2 text-muted">item per halaman</span>
            </form>
        </div>
        @endif
        <div class="card-body">
            @if($soEvents->isEmpty())
                <p class="text-center">Belum ada SO Item Rahil yang dibuat.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="dataTableSoEvents" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama SO</th>
                                <th>Status</th>
                                <th>Deskripsi</th>
                                <th>Dibuat Oleh</th>
                                <th>Tanggal Mulai</th>
                                <th>Tanggal Selesai</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($soEvents as $index => $event)
                                <tr>
                                    <td>{{ $soEvents->firstItem() + $index }}</td>
                                    <td>{{ $event->name }}</td>
                                    <td>
                                        <span class="badge bg-{{ $event->status == 'active' ? 'success' : ($event->status == 'completed' ? 'info' : ($event->status == 'pending' ? 'warning' : 'secondary')) }}">
                                            {{ ucfirst($event->status) }}
                                        </span>
                                    </td>
                                    <td>{{ Str::limit($event->description, 50) }}</td>
                                    <td>{{ $event->createdBy->username ?? 'N/A' }}</td>
                                    <td>{{ $event->start_date ? $event->start_date->format('d M Y') : '-' }}</td>
                                    <td>{{ $event->end_date ? $event->end_date->format('d M Y') : '-' }}</td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="bi bi-gear-fill"></i> Aksi
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('admin.so-events.show', $event->id) }}" title="Kelola Produk">
                                                        <i class="bi bi-card-checklist me-2"></i>Kelola Produk
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('admin.so-events.edit', $event->id) }}" title="Edit SO Item Rahil">
                                                        <i class="bi bi-pencil-square me-2"></i>Edit
                                                    </a>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <form action="{{ route('admin.so-events.destroy', $event->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus SO Item Rahil \'{{ $event->name }}\' ini? Semua produk terkait juga akan terhapus.');" style="display:inline-block; width:100%;">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="dropdown-item text-danger" title="Hapus SO Item Rahil">
                                                            <i class="bi bi-trash me-2"></i>Hapus
                                                        </button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    {{ $soEvents->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

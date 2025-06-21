@extends('layouts.app')

@section('title', 'Manajemen Aplikasi Flutter')

@section('content')
<div class="container-fluid">
    <div class="row mb-4 align-items-center">
        <div class="col-md-8">
            <h1 class="h3 text-primary mb-0">Manajemen Aplikasi Flutter</h1>
            <p class="text-muted mb-0">Kelola versi aplikasi, catatan rilis, dan distribusi APK internal.</p>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <button type="button" class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#uploadApkModal">
                <i class="bi bi-upload me-1"></i> Tambah Versi Baru
            </button>
        </div>
    </div>

    @include('layouts.flash-messages')

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom-0 pb-0">
                    <h5 class="mb-0 text-primary"><i class="bi bi-list-ul me-2"></i>Daftar Versi Aplikasi</h5>
                </div>
                <div class="card-body p-0">
                    @if($versions->count())
                    <div class="table-responsive">
                        <table class="table table-borderless table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center">#</th>
                                    <th>Versi</th>
                                    <th>Link APK</th>
                                    <th>Catatan</th>
                                    <th>Status</th>
                                    <th>Tanggal</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($versions as $i => $version)
                                <tr>
                                    <td class="text-center text-muted">{{ $i+1 }}</td>
                                    <td class="fw-semibold">{{ $version->version_name }}</td>
                                    <td>
                                        @if($version->file_name)
                                        <a href="{{ $version->file_name }}" target="_blank" rel="noopener" class="badge bg-primary text-white px-3 py-2"><i class="bi bi-google-drive me-1"></i>Download</a>
                                        @else
                                        <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-muted">{{ Str::limit($version->release_notes, 40) ?: '-' }}</td>
                                    <td>
                                        @if($version->is_active)
                                        <span class="badge bg-success">Aktif</span>
                                        @else
                                        <span class="badge bg-secondary">Tidak Aktif</span>
                                        @endif
                                    </td>
                                    <td><small>{{ $version->created_at->format('d/m/Y H:i') }}</small></td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="dropdownMenuButton{{ $version->id }}" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="bi bi-three-dots-vertical"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton{{ $version->id }}">
                                                @if($version->file_name)
                                                <li>
                                                    <a class="dropdown-item" href="{{ $version->file_name }}" target="_blank">
                                                        <i class="bi bi-download me-2"></i> Download APK
                                                    </a>
                                                </li>
                                                @endif
                                                <li>
                                                    <button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#editApkModal"
                                                        data-id="{{ $version->id }}"
                                                        data-version_name="{{ $version->version_name }}"
                                                        data-file_name="{{ $version->file_name }}"
                                                        data-release_notes="{{ $version->release_notes }}">
                                                        <i class="bi bi-pencil-square me-2"></i> Edit
                                                    </button>
                                                </li>
                                                @if($version->is_active)
                                                <li>
                                                    <form action="{{ route('admin.flutter_app.deactivate_version') }}" method="POST">
                                                        @csrf
                                                        <input type="hidden" name="version_id" value="{{ $version->id }}">
                                                        <button type="submit" class="dropdown-item">
                                                            <i class="bi bi-x-circle me-2"></i> Nonaktifkan
                                                        </button>
                                                    </form>
                                                </li>
                                                @else
                                                <li>
                                                    <form action="{{ route('admin.flutter_app.set_active_version') }}" method="POST">
                                                        @csrf
                                                        <input type="hidden" name="version_id" value="{{ $version->id }}">
                                                        <button type="submit" class="dropdown-item">
                                                            <i class="bi bi-check-circle me-2"></i> Aktifkan
                                                        </button>
                                                    </form>
                                                </li>
                                                @endif
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <form action="{{ route('admin.flutter_app.delete') }}" method="POST">
                                                        @csrf
                                                        <input type="hidden" name="version_id" value="{{ $version->id }}">
                                                        <button type="submit" class="dropdown-item text-danger" onclick="return confirm('Anda yakin ingin menghapus versi {{ $version->version_name }}?')">
                                                            <i class="bi bi-trash me-2"></i> Hapus
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
                    @else
                    <div class="alert alert-info m-3">
                        <i class="bi bi-info-circle-fill me-2"></i> Belum ada versi aplikasi yang diunggah.
                    </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom-0 pb-0">
                    <h5 class="mb-0 text-primary"><i class="bi bi-info-circle me-2"></i>Informasi</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2"><i class="bi bi-upload me-2 text-primary"></i>Tambah versi aplikasi dengan link Google Drive.</li>
                        <li class="mb-2"><i class="bi bi-card-text me-2 text-primary"></i>Catat perubahan rilis setiap versi.</li>
                        <li class="mb-2"><i class="bi bi-check2-circle me-2 text-success"></i>Tandai versi aktif untuk update otomatis.</li>
                        <li class="mb-2"><i class="bi bi-download me-2 text-primary"></i>Download APK langsung dari Google Drive.</li>
                        <li><i class="bi bi-share me-2 text-primary"></i>Distribusi internal tanpa Play Store.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modals remain the same as your original code -->
@include('admin.flutter_app.modals')

@endsection

@push('styles')
<style>
    .table-responsive {
        overflow: visible !important;
    }
    
    .dropdown-toggle::after {
        display: none;
    }
    
    .dropdown-menu {
        min-width: 180px;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        border: none;
    }
    
    .dropdown-item {
        padding: 0.5rem 1rem;
        font-size: 0.875rem;
    }
    
    .dropdown-item i {
        width: 18px;
        text-align: center;
        margin-right: 8px;
    }
    
    .btn-sm.dropdown-toggle {
        padding: 0.25rem 0.5rem;
    }
    
    .badge.bg-primary.text-white {
        font-size: 0.85em;
        font-weight: 500;
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Check if we should open the upload modal
    var shouldOpenModal = {!! json_encode(session('open_upload_modal') || (isset($errors) && $errors->hasBag('uploadForm') && $errors->getBag('uploadForm')->any())) !!};
    
    if (shouldOpenModal) {
        var uploadModal = new bootstrap.Modal(document.getElementById('uploadApkModal'));
        uploadModal.show();
    }

    // Handle edit modal
    var editApkModal = document.getElementById('editApkModal');
    if (editApkModal) {
        editApkModal.addEventListener('show.bs.modal', function(event) {
            var button = event.relatedTarget;
            document.getElementById('edit_version_id').value = button.getAttribute('data-id');
            document.getElementById('edit_version_name').value = button.getAttribute('data-version_name');
            document.getElementById('edit_file_name').value = button.getAttribute('data-file_name');
            document.getElementById('edit_release_notes').value = button.getAttribute('data-release_notes');
        });
    }
    
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
})
</script>
@endpush
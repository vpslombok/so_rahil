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
                                        @if($version->gdrive_link)
                                        <a href="{{ $version->gdrive_link }}" target="_blank" rel="noopener" class="badge bg-primary text-white px-3 py-2"><i class="bi bi-google-drive me-1"></i>Download</a>
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
                                        <div class="btn-group btn-group-sm" role="group">
                                            @if($version->gdrive_link)
                                            <a href="{{ $version->gdrive_link }}" target="_blank" rel="noopener" class="btn btn-outline-primary" title="Download APK">
                                                <i class="bi bi-download"></i>
                                            </a>
                                            @endif
                                            @if($version->is_active)
                                            <form action="{{ route('admin.flutter_app.deactivate_version') }}" method="POST" class="d-inline">
                                                @csrf
                                                <input type="hidden" name="version_id" value="{{ $version->id }}">
                                                <button type="submit" class="btn btn-outline-warning" title="Nonaktifkan">
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                            </form>
                                            @else
                                            <form action="{{ route('admin.flutter_app.set_active_version') }}" method="POST" class="d-inline">
                                                @csrf
                                                <input type="hidden" name="version_id" value="{{ $version->id }}">
                                                <button type="submit" class="btn btn-outline-success" title="Aktifkan">
                                                    <i class="bi bi-check2-circle"></i>
                                                </button>
                                            </form>
                                            @endif
                                            <form id="delete-apk-{{ $version->id }}-form" action="{{ route('admin.flutter_app.delete') }}" method="POST" class="d-inline">
                                                @csrf
                                                <input type="hidden" name="version_id" value="{{ $version->id }}">
                                                <button type="submit" class="btn btn-outline-danger" title="Hapus" onclick="return confirm('Anda yakin ingin menghapus versi {{ $version->version_name }}?')">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
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
<!-- Modal Upload APK -->
<div class="modal fade" id="uploadApkModal" tabindex="-1" aria-labelledby="uploadApkModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="uploadApkModalLabel"><i class="bi bi-google-drive me-2"></i>Tambah Versi Aplikasi (Link Google Drive)</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('admin.flutter_app.upload') }}" method="POST">
                @csrf
                <div class="modal-body">
                    @if($errors->uploadForm->any())
                    <div class="alert alert-danger">
                        <p class="fw-bold">Terdapat kesalahan pada input Anda:</p>
                        <ul>
                            @foreach ($errors->uploadForm->all() as $error)
                            <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                    <div class="mb-3">
                        <label for="modal_gdrive_link" class="form-label">Link Google Drive APK <span class="text-danger">*</span></label>
                        <input class="form-control @error('gdrive_link', 'uploadForm') is-invalid @enderror" type="url" id="modal_gdrive_link" name="gdrive_link" placeholder="https://drive.google.com/file/d/xxx/view?usp=sharing" value="{{ old('gdrive_link') }}" required>
                        @error('gdrive_link', 'uploadForm')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">Pastikan link Google Drive dapat diakses publik (Anyone with the link can view).</small>
                    </div>
                    <div class="mb-3">
                        <label for="modal_version_name" class="form-label">Versi Aplikasi <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('version_name', 'uploadForm') is-invalid @enderror" name="version_name" id="modal_version_name" placeholder="Contoh: 1.0.5" value="{{ old('version_name') }}" required>
                        @error('version_name', 'uploadForm')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="modal_release_notes" class="form-label">Catatan Rilis</label>
                        <textarea class="form-control @error('release_notes', 'uploadForm') is-invalid @enderror" name="release_notes" id="modal_release_notes" rows="3" placeholder="Contoh: Perbaikan bug kecil dan peningkatan performa.">{{ old('release_notes') }}</textarea>
                        @error('release_notes', 'uploadForm')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> Simpan Versi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
@push('styles')
<style>
    .table-responsive {
        overflow: visible !important;
        position: relative;
    }

    .table .dropdown-menu {
        z-index: 1050;
    }

    .badge.bg-primary.text-white {
        font-size: 0.95em;
        font-weight: 500;
        letter-spacing: 0.5px;
    }

    .modal-header.bg-primary {
        background: linear-gradient(90deg, #0d6efd 60%, #0a58ca 100%);
    }

    .modal-footer.bg-light {
        border-top: 1px solid #e9ecef;
    }

    .btn-outline-primary,
    .btn-outline-success,
    .btn-outline-warning,
    .btn-outline-danger {
        min-width: 36px;
    }

    .d-flex.gap-2>* {
        margin-right: 0 !important;
    }
</style>
@endpush
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var shouldOpenModal = {
            {
                (session('open_upload_modal') || (isset($errors) && isset($errors['uploadForm']) && $errors['uploadForm'] - > any())) ? 'true' : 'false'
            }
        };
        if (shouldOpenModal === 'true') {
            var uploadModal = new bootstrap.Modal(document.getElementById('uploadApkModal'));
            uploadModal.show();
        }
    });
</script>
@endpush
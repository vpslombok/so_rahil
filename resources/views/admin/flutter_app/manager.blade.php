@extends('layouts.app')

@section('title', 'Manajemen Aplikasi Flutter')

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-gray-800">Manajemen Aplikasi Flutter</h1>
    </div>

    @include('layouts.flash-messages')

    <div class="row">
        {{-- Kiri: Form Upload + Daftar --}}
        <div class="col-lg-8 mb-4">
            {{-- Daftar APK --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0 text-primary">Daftar Versi Aplikasi</h5>
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#uploadApkModal">
                        <i class="bi bi-upload me-1"></i> Upload Aplikasi Baru
                    </button>
                </div>
                <div class="card-body p-0">
                    @if($versions->count())
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Versi</th>
                                    <th>Nama File</th>
                                    <th>Ukuran</th>
                                    <th>Catatan</th>
                                    <th>Status</th>
                                    <th>Tanggal</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($versions as $version)
                                <tr>
                                    <td class="fw-semibold">{{ $version->version_name }}</td>
                                    <td>{{ $version->file_name }}</td>
                                    <td>{{ $version->formatted_size }}</td>
                                    <td>{{ Str::limit($version->release_notes, 40) ?: '-' }}</td>
                                    <td>
                                        @if($version->is_active)
                                        <span class="badge bg-success">Aktif</span>
                                        @else
                                        <span class="badge bg-secondary">Tidak Aktif</span>
                                        @endif
                                    </td>
                                    <td>{{ $version->created_at->format('d/m/Y H:i') }}</td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="actionsDropdown{{ $version->id }}" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="bi bi-three-dots-vertical"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="actionsDropdown{{ $version->id }}">
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('admin.flutter_app.download', ['version_id' => $version->id]) }}">
                                                        <i class="bi bi-download me-2"></i>Download
                                                    </a>
                                                </li>
                                                @if($version->is_active)
                                                <li>
                                                    <form action="{{ route('admin.flutter_app.deactivate_version') }}" method="POST">
                                                        @csrf
                                                        <input type="hidden" name="version_id" value="{{ $version->id }}">
                                                        <button type="submit" class="dropdown-item text-warning">
                                                            <i class="bi bi-x-lg me-2"></i>Nonaktifkan
                                                        </button>
                                                    </form>
                                                </li>
                                                @else
                                                <li>
                                                    <form action="{{ route('admin.flutter_app.set_active_version') }}" method="POST">
                                                        @csrf
                                                        <input type="hidden" name="version_id" value="{{ $version->id }}">
                                                        <button type="submit" class="dropdown-item text-success">
                                                            <i class="bi bi-check2-circle me-2"></i>Aktifkan
                                                        </button>
                                                    </form>
                                                </li>
                                                @endif
                                                <li>
                                                    <hr class="dropdown-divider">
                                                </li>
                                                <li>
                                                    <button class="dropdown-item text-danger" type="button"
                                                        onclick="if(confirm('Anda yakin ingin menghapus versi {{ $version->version_name }}? File APK juga akan dihapus.')) { document.getElementById('delete-apk-{{ $version->id }}-form').submit(); }">
                                                        <i class="bi bi-trash me-2"></i>Hapus
                                                    </button>
                                                    <form id="delete-apk-{{ $version->id }}-form" action="{{ route('admin.flutter_app.delete') }}" method="POST" style="display: none;">
                                                        @csrf
                                                        <input type="hidden" name="version_id" value="{{ $version->id }}">
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                        {{-- Kode lama dengan btn-group, bisa dihapus setelah dropdown berfungsi --}}
                                        {{-- <div class="btn-group btn-group-sm" role="group">
                                            ... (tombol-tombol lama) ...
                                        </div> --}}
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

        {{-- Kanan: Informasi --}}
        <div class="col-lg-4 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0 text-primary">Informasi</h5>
                </div>
                <div class="card-body">
                    <p>Gunakan halaman ini untuk mengelola versi aplikasi Flutter Anda secara internal.</p>
                    <ul class="mb-0">
                        <li>Upload file APK</li>
                        <li>Catatan rilis</li>
                        <li>Penandaan versi aktif</li>
                        <li>Download & hapus APK</li>
                        <li>Distribusi langsung tanpa Play Store</li>
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
            <div class="modal-header">
                <h5 class="modal-title" id="uploadApkModalLabel">Upload Aplikasi Baru (.APK)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('admin.flutter_app.upload') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    {{-- Flash Messages khusus upload bisa diletakkan di sini jika ingin di dalam modal, atau biarkan di atas --}}
                    {{-- Jika ada error validasi dari controller, modal ini akan perlu dibuka kembali --}}
                    @if($errors->uploadForm->any()) {{-- Menggunakan error bag kustom --}}
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
                        <label for="modal_apk_file" class="form-label">File APK (.apk) <span class="text-danger">*</span></label>
                        <input class="form-control @error('apk_file', 'uploadForm') is-invalid @enderror" type="file" id="modal_apk_file" name="apk_file" accept=".apk" required>
                        @error('apk_file', 'uploadForm')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
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
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-upload me-1"></i> Upload Aplikasi
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
        /* pastikan dropdown lebih tinggi dari elemen lain */
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Jika ada error validasi dari form upload, buka modalnya
        @if(session('open_upload_modal') || $errors->uploadForm->any())
        var uploadModal = new bootstrap.Modal(document.getElementById('uploadApkModal'));
        uploadModal.show();
        @endif
    });
</script>
@endpush
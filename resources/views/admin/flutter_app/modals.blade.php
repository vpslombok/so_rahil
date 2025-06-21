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
                        <label for="modal_file_name" class="form-label">Link Google Drive APK <span class="text-danger">*</span></label>
                        <input class="form-control @error('file_name', 'uploadForm') is-invalid @enderror" type="url" id="modal_file_name" name="file_name" placeholder="https://drive.google.com/file/d/xxx/view?usp=sharing" value="{{ old('file_name') }}" required>
                        @error('file_name', 'uploadForm')
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
<!-- Modal Edit APK -->
<div class="modal fade" id="editApkModal" tabindex="-1" aria-labelledby="editApkModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="editApkModalLabel"><i class="bi bi-pencil-square me-2"></i>Edit Versi Aplikasi</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editApkForm" action="{{ route('admin.flutter_app.update') }}" method="POST">
                @csrf
                <input type="hidden" name="version_id" id="edit_version_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_file_name" class="form-label">Link Google Drive APK <span class="text-danger">*</span></label>
                        <input class="form-control" type="url" id="edit_file_name" name="file_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_version_name" class="form-label">Versi Aplikasi <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="version_name" id="edit_version_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_release_notes" class="form-label">Catatan Rilis</label>
                        <textarea class="form-control" name="release_notes" id="edit_release_notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-info">
                        <i class="bi bi-save me-1"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@extends('layouts.app')

@section('title', 'Manajemen Backup Database')

@section('content')
<div class="container-fluid">
    <div class="text-center pt-2 pb-2 mb-3 border-bottom">
        <h1 class="h2 d-inline-block mb-0 me-2 align-middle">Backup Database</h1>
    </div>

    @include('layouts.flash-messages')

    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Daftar File Backup Database</h5>
                    <div>
                        <a href="{{ route('admin.database.backup.create') }}" class="btn btn-sm btn-primary">
                            <i class="bi bi-plus-circle"></i> Backup Sekarang
                        </a>
                        <a href="{{ route('admin.database.migrate') }}" class="btn btn-sm btn-warning ms-2">
                            <i class="bi bi-lightning-charge"></i> Jalankan Migration
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="alert alert-info small mb-3">
                        <b>Restore:</b> hanya mengimpor data dari file backup, <u>akan gagal jika tabel sudah ada</u>.<br>
                        <b>Force Restore:</b> <span class="text-danger">menghapus semua tabel & views (kecuali migrations) sebelum mengimpor backup</span>, cocok untuk reset total database.<br>
                        <b>Tips:</b> Gunakan file backup hasil <code>phpMyAdmin</code> atau fitur backup di halaman ini. Pastikan backup sesuai dengan struktur database aplikasi.
                    </div>
                    <form action="{{ route('admin.database.backup.upload') }}" method="POST" enctype="multipart/form-data" class="mb-4">
                        @csrf
                        <div class="row g-2 align-items-center">
                            <div class="col-auto">
                                <input type="file" name="backup_file" accept=".sql,.txt" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-sm btn-secondary">
                                    <i class="bi bi-upload"></i> Upload Backup
                                </button>
                            </div>
                            <div class="col-auto text-muted small">
                                Maksimal 10MB, format: .sql atau .txt
                            </div>
                        </div>
                    </form>
                    @if(!empty($backups) && count($backups) > 0)
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Nama File</th>
                                    <th>Ukuran</th>
                                    <th>Tanggal Backup</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($backups as $i => $backup)
                                <tr>
                                    <td>{{ $i+1 }}</td>
                                    <td><code>{{ $backup['name'] }}</code></td>
                                    <td>{{ $backup['size'] }}</td>
                                    <td>{{ $backup['date'] }}</td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="aksiDropdown{{ $i }}" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="bi bi-list"></i> Aksi
                                            </button>
                                            <ul class="dropdown-menu" aria-labelledby="aksiDropdown{{ $i }}">
                                                <li>
                                                    <a href="{{ route('admin.database.backup.download', $backup['name']) }}" class="dropdown-item">
                                                        <i class="bi bi-download"></i> Download
                                                    </a>
                                                </li>
                                                <li>
                                                    <form action="{{ route('admin.database.backup.restore', $backup['name']) }}" method="POST" onsubmit="return confirm('Restore backup ini? Data saat ini akan diganti!\n\nRestore: hanya mengimpor data, gagal jika tabel sudah ada.');">
                                                        @csrf
                                                        <button type="submit" class="dropdown-item text-warning" title="Restore: hanya mengimpor data, gagal jika tabel sudah ada.">
                                                            <i class="bi bi-arrow-clockwise"></i> Restore
                                                        </button>
                                                    </form>
                                                </li>
                                                <li>
                                                    <form action="{{ route('admin.database.backup.restore', [$backup['name']]) }}" method="POST" onsubmit="return confirm('Force Restore akan menghapus semua tabel & views (kecuali migrations) sebelum restore! Seluruh data akan diganti. Lanjutkan?');">
                                                        @csrf
                                                        <input type="hidden" name="force" value="1">
                                                        <button type="submit" class="dropdown-item text-danger" title="Force Restore: hapus semua tabel & views sebelum restore">
                                                            <i class="bi bi-exclamation-triangle"></i> Force Restore
                                                        </button>
                                                    </form>
                                                </li>
                                                <li>
                                                    <form action="{{ route('admin.database.backup.delete', $backup['name']) }}" method="POST" class="delete-backup-form">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="button" class="dropdown-item text-danger btn-delete-backup">
                                                            <i class="bi bi-trash"></i> Hapus
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
                    <p class="text-muted">Belum ada file backup database.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.btn-delete-backup').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const form = btn.closest('form');
                Swal.fire({
                    title: 'Hapus file backup ini?',
                    text: 'File backup akan dihapus permanen!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, hapus!',
                    cancelButtonText: 'Batal',
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    });
</script>
@endpush
@extends('layouts.app')

@section('title', 'Log Penggunaan REST API')

@section('content')
<div class="container py-4">
    @include('layouts.flash-messages')
    <div class="row mb-4">
        <div class="col">
            <h1 class="h3 text-primary mb-0"><i class="bi bi-plug-fill me-2"></i>Log Penggunaan REST API</h1>
            <p class="text-muted">Riwayat akses endpoint REST API oleh user aplikasi.</p>
        </div>
    </div>
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form id="bulkDeleteForm" action="{{ route('admin.api_log.bulk_delete') }}" method="POST" class="mb-2">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger btn-sm d-none" id="btnBulkDelete" onclick="return confirm('Hapus semua log terpilih?')">
                    <i class="bi bi-trash"></i> Hapus Data Terpilih
                </button>
            </form>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th><input type="checkbox" id="checkAll"></th>
                            <th>#</th>
                            <th>User</th>
                            <th>Endpoint</th>
                            <th>Method</th>
                            <th>Waktu</th>
                            <th>IP</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $i => $log)
                        <tr>
                            <td><input type="checkbox" name="ids[]" value="{{ $log->id }}" form="bulkDeleteForm" class="row-check"></td>
                            <td>{{ $i+1 }}</td>
                            <td>{{ $log->user_name ?? '-' }}</td>
                            <td><code>{{ $log->endpoint }}</code></td>
                            <td><span class="badge bg-{{ $log->method === 'GET' ? 'primary' : 'success' }}">{{ $log->method }}</span></td>
                            <td>{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                            <td>{{ $log->ip_address }}</td>
                            <td class="text-center">
                                <form action="{{ route('admin.api_log.delete', $log->id) }}" method="POST" class="d-inline-block delete-log-form">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="btn btn-sm btn-danger btn-delete-log" title="Hapus log ini"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted">Belum ada log penggunaan REST API.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4 alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                Hanya menampilkan log penggunaan REST API per user. Untuk fitur filter, pencarian, atau detail, silakan kembangkan sesuai kebutuhan.
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Checkbox master
        const checkAll = document.getElementById('checkAll');
        const rowChecks = document.querySelectorAll('.row-check');
        const btnBulkDelete = document.getElementById('btnBulkDelete');

        function updateBulkDeleteBtn() {
            const anyChecked = Array.from(rowChecks).some(cb => cb.checked);
            btnBulkDelete.classList.toggle('d-none', !anyChecked);
        }
        if (checkAll) {
            checkAll.addEventListener('change', function() {
                rowChecks.forEach(cb => {
                    cb.checked = checkAll.checked;
                });
                updateBulkDeleteBtn();
            });
        }
        rowChecks.forEach(cb => {
            cb.addEventListener('change', function() {
                if (!cb.checked) checkAll.checked = false;
                updateBulkDeleteBtn();
            });
        });
        updateBulkDeleteBtn();
        // SweetAlert untuk hapus satuan
        document.querySelectorAll('.btn-delete-log').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const form = btn.closest('form');
                Swal.fire({
                    title: 'Hapus log ini?',
                    text: 'Log penggunaan REST API akan dihapus permanen!',
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
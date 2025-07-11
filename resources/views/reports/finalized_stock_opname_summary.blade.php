@extends('layouts.app')

@section('title', 'Laporan Selisih Stok Final')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-md-11 col-lg-10 col-xl-9">
            <div class="card shadow rounded-4 border-0">
                <div class="card-header bg-gradient-primary text-white rounded-top-4 d-flex align-items-center justify-content-between flex-wrap">
                    <h4 class="mb-0"><i class="fas fa-clipboard-list mr-2"></i> Laporan Selisih Stok <span class="badge bg-light text-primary">Finalisasi</span></h4>
                </div>
                <div class="card-body bg-light rounded-bottom-4 p-2 p-md-4">
                    @if($finalizedGroups->isEmpty())
                    <div class="d-flex flex-column align-items-center justify-content-center py-5">
                        <i class="fas fa-box-open fa-3x text-secondary mb-3"></i>
                        <h5 class="text-secondary mb-2">Belum ada data stok opname yang difinalisasi.</h5>
                        <p class="text-muted">Silakan lakukan finalisasi SO untuk melihat ringkasan di sini.</p>
                    </div>
                    @else
                    <div class="table-responsive">
                        <form id="bulkDeleteForm" action="{{ route('stock_audit_report.destroy_group') }}" method="POST">
                            @push('scripts')
                            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                            <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    // SweetAlert for bulk delete
                                    const bulkDeleteForm = document.getElementById('bulkDeleteForm');
                                    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
                                    if (bulkDeleteForm && bulkDeleteBtn) {
                                        bulkDeleteForm.addEventListener('submit', function(e) {
                                            if (!bulkDeleteBtn.disabled) {
                                                e.preventDefault();
                                                Swal.fire({
                                                    title: 'Hapus semua data terpilih?',
                                                    text: 'Tindakan ini tidak dapat dibatalkan. Data yang dihapus tidak bisa dikembalikan!',
                                                    icon: 'warning',
                                                    showCancelButton: true,
                                                    confirmButtonColor: '#d33',
                                                    cancelButtonColor: '#3085d6',
                                                    confirmButtonText: 'Ya, hapus!',
                                                    cancelButtonText: 'Batal',
                                                    reverseButtons: true
                                                }).then((result) => {
                                                    if (result.isConfirmed) {
                                                        bulkDeleteForm.submit();
                                                    }
                                                });
                                            }
                                        });
                                    }
                                });
                            </script>
                            @endpush
                            @csrf
                            @method('DELETE')
                            <div class="mb-2 d-flex flex-wrap align-items-center gap-2">
                                <button type="submit" class="btn btn-danger btn-sm d-flex align-items-center gap-2 shadow-sm bulk-delete-btn-custom" id="bulkDeleteBtn" disabled style="border-radius:2em; font-weight:600; letter-spacing:0.5px; padding:0.5em 1.2em; transition:background 0.2s;">
                                    <span class="d-flex align-items-center justify-content-center" style="background:rgba(255,255,255,0.15); border-radius:50%; width:2em; height:2em;">
                                        <i class="fas fa-trash" style="font-size:1.1em;"></i>
                                    </span>
                                    <span>Hapus Data Terpilih</span>
                                </button>
                                @push('styles')
                                <style>
                                    .bulk-delete-btn-custom:not(:disabled):hover {
                                        background: linear-gradient(90deg, #e53935 0%, #b71c1c 100%) !important;
                                        color: #fff !important;
                                        box-shadow: 0 2px 8px rgba(229, 57, 53, 0.12);
                                        transform: translateY(-1px) scale(1.03);
                                    }

                                    .bulk-delete-btn-custom:disabled {
                                        opacity: 0.7;
                                        cursor: not-allowed;
                                    }

                                    .bulk-delete-btn-custom span {
                                        transition: color 0.2s;
                                    }

                                    .bulk-delete-btn-custom:active {
                                        background: #b71c1c !important;
                                        color: #fff !important;
                                    }
                                </style>
                                @endpush
                                <span id="selectedCount" class="text-muted small"></span>
                            </div>
                            <table class="table table-hover align-middle bg-white rounded shadow-sm mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th class="text-center" style="width: 5%; min-width: 40px;">
                                            <input type="checkbox" id="selectAllRows" />
                                        </th>
                                        <th style="min-width: 120px;">Nomor Nota</th>
                                        <th style="min-width: 120px;">Event SO</th>
                                        <th style="min-width: 100px;">Finalisasi Oleh</th>
                                        <th style="min-width: 120px;">Tanggal Finalisasi</th>
                                        <th class="text-center" style="width: 15%; min-width: 80px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($finalizedGroups as $index => $group)
                                    <tr>
                                        <td class="text-center">
                                            <input type="checkbox" name="bulk[]" class="row-checkbox" value="{{ $group->nomor_nota }}|{{ $group->stock_opname_event_id }}|{{ $group->user_id }}" />
                                        </td>
                                        <td>
                                            <span class="fw-bold text-primary small">{{ $group->nomor_nota }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-info text-dark small">{{ $group->stockOpnameEvent->name ?? 'N/A' }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary small">{{ $group->user->username ?? 'N/A' }}</span>
                                        </td>
                                        <td>
                                            <i class="far fa-calendar-check text-success"></i>
                                            <span class="ms-1 small">{{ $group->latest_checked_at ? \Carbon\Carbon::parse($group->latest_checked_at)->isoFormat('D MMM YYYY, HH:mm') : '-' }}</span>
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" id="aksiDropdown{{ $group->nomor_nota }}" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="aksiDropdown{{ $group->nomor_nota }}">
                                                    <li>
                                                        <a href="{{ route('stock_audit_report.details_by_nota', ['nomor_nota' => $group->nomor_nota]) }}" class="dropdown-item" title="Lihat Detail Item">
                                                            <i class="fas fa-eye text-primary me-2"></i> Detail
                                                        </a>
                                                    </li>
                                                    @if(Auth::user()->role === 'admin' || Auth::id() == $group->user_id)
                                                    <li>
                                                        <form action="{{ route('stock_audit_report.destroy_group') }}" method="POST" onsubmit="return confirm('Anda yakin ingin menghapus data finalisasi SO ini? Tindakan ini tidak dapat dibatalkan.');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <input type="hidden" name="nomor_nota" value="{{ $group->nomor_nota }}">
                                                            <input type="hidden" name="stock_opname_event_id" value="{{ $group->stock_opname_event_id }}">
                                                            <input type="hidden" name="user_id_for_deletion" value="{{ $group->user_id }}">
                                                            <button type="submit" class="dropdown-item text-danger">
                                                                <i class="fas fa-trash me-2"></i> Hapus
                                                            </button>
                                                        </form>
                                                    </li>
                                                    @endif
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </form>
                        @push('scripts')
                        <script>
                            // Bulk select logic
                            document.addEventListener('DOMContentLoaded', function() {
                                const selectAll = document.getElementById('selectAllRows');
                                const checkboxes = document.querySelectorAll('.row-checkbox');
                                const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
                                const selectedCount = document.getElementById('selectedCount');
                                const form = document.getElementById('bulkDeleteForm');

                                function updateBulkState() {
                                    const checked = document.querySelectorAll('.row-checkbox:checked');
                                    bulkDeleteBtn.disabled = checked.length === 0;
                                    selectedCount.textContent = checked.length > 0 ? `${checked.length} data dipilih` : '';
                                }

                                if (selectAll) {
                                    selectAll.addEventListener('change', function() {
                                        checkboxes.forEach(cb => {
                                            cb.checked = selectAll.checked;
                                        });
                                        updateBulkState();
                                    });
                                }
                                checkboxes.forEach(cb => {
                                    cb.addEventListener('change', function() {
                                        if (!this.checked && selectAll.checked) selectAll.checked = false;
                                        updateBulkState();
                                    });
                                });
                                updateBulkState();

                                // On submit, add hidden input for each checked row
                                if (form) {
                                    form.addEventListener('submit', function(e) {
                                        // Remove previous hidden inputs
                                        form.querySelectorAll('.bulk-hidden').forEach(el => el.remove());
                                        document.querySelectorAll('.row-checkbox:checked').forEach(cb => {
                                            const [nomor_nota, stock_opname_event_id, user_id] = cb.value.split('|');
                                            form.appendChild(Object.assign(document.createElement('input'), {
                                                type: 'hidden',
                                                name: 'bulk_nomor_nota[]',
                                                value: nomor_nota,
                                                className: 'bulk-hidden'
                                            }));
                                            form.appendChild(Object.assign(document.createElement('input'), {
                                                type: 'hidden',
                                                name: 'bulk_stock_opname_event_id[]',
                                                value: stock_opname_event_id,
                                                className: 'bulk-hidden'
                                            }));
                                            form.appendChild(Object.assign(document.createElement('input'), {
                                                type: 'hidden',
                                                name: 'bulk_user_id[]',
                                                value: user_id,
                                                className: 'bulk-hidden'
                                            }));
                                        });
                                    });
                                }
                            });
                        </script>
                        @endpush
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
        .card-header.bg-gradient-primary {
            background: linear-gradient(90deg, #007bff 0%, #0056b3 100%) !important;
        }

        .table th,
        .table td {
            vertical-align: middle;
            white-space: nowrap;
        }

        .table thead th {
            background-color: #f8f9fa;
            font-weight: 600;
        }

        .btn-outline-primary,
        .btn-outline-danger {
            border-radius: 50%;
            width: 36px;
            height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0;
        }

        .btn-outline-primary i,
        .btn-outline-danger i {
            font-size: 1.1rem;
        }

        .badge {
            font-size: 0.95em;
            padding: 0.5em 0.8em;
            border-radius: 0.7em;
        }

        @media (max-width: 767.98px) {
            .card-header.bg-gradient-primary h4 {
                font-size: 1.1rem;
            }

            .table th,
            .table td {
                font-size: 0.92em;
                padding: 0.4em 0.3em;
            }

            .table thead th {
                font-size: 0.95em;
            }

            .badge {
                font-size: 0.85em;
                padding: 0.35em 0.6em;
            }

            .btn-outline-primary,
            .btn-outline-danger {
                width: 30px;
                height: 30px;
            }
        }

        @media (max-width: 575.98px) {

            .table th,
            .table td {
                font-size: 0.85em;
                padding: 0.3em 0.2em;
            }

            .table thead th {
                font-size: 0.9em;
            }

            .badge {
                font-size: 0.75em;
                padding: 0.25em 0.5em;
            }

            .card-header.bg-gradient-primary h4 {
                font-size: 1em;
            }
        }
    </style>
    @endpush
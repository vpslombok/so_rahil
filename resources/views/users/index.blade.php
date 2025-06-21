@extends('layouts.app')

@section('title', 'Manajemen User')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Manajemen User</h1>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">
            <i class="fas fa-user-plus me-1"></i> Tambah User Baru
        </button>
    </div>

    {{-- Flash Messages --}}
    @if (session('success_message_user'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i> {{ session('success_message_user') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif
    @if (session('error_message_user'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i> {{ session('error_message_user') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    {{-- User Table --}}
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Daftar User</h6>
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-cog"></i>
                </button>
                <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                    <li><a class="dropdown-item" href="#"><i class="fas fa-file-export me-2"></i>Export Data</a></li>
                </ul>
            </div>
        </div>
        <div class="card-body">
            @if (!isset($users) || $users->isEmpty())
            <div class="text-center py-5">
                <i class="fas fa-users-slash fa-3x text-gray-400 mb-3"></i>
                <h5 class="text-gray-600">Tidak ada data user</h5>
                <p class="mb-4">Mulai dengan menambahkan user baru</p>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">
                    <i class="fas fa-user-plus me-1"></i> Tambah User
                </button>
            </div>
            @else
            <div class="table-responsive">
                <table id="usersTable" class="table table-hover" style="width:100%">
                    <thead class="bg-light">
                        <tr>
                            <th width="5%">#</th>
                            <th>Nama</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th width="20%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $user)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    {{-- Anda bisa menambahkan avatar atau inisial di sini jika mau --}}
                                    <div>
                                        <div class="fw-bold">{{ $user->name }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div>
                                        <div class="fw-bold">{{ $user->username }}</div>
                                        <div class="text-muted small">Created: {{ $user->created_at->format('d M Y') }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-{{ $user->role == 'admin' ? 'danger' : 'primary' }}">
                                    {{ ucfirst($user->role) }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-success">
                                    <i class="fas fa-check-circle me-1"></i> Active
                                </span>
                            </td>
                            <td>
                                <div class="d-flex">
                                    <button type="button" class="btn btn-sm btn-icon btn-warning me-2 edit-user-btn"
                                        data-bs-toggle="modal" data-bs-target="#editUserModal"
                                        data-id="{{ $user->id }}"
                                        data-name="{{ $user->name }}"
                                        data-username="{{ $user->username }}"
                                        data-role="{{ $user->role }}"
                                        title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>

                                    <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" class="delete-form">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-icon btn-danger" title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($users->hasPages())
            <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap">
                <div class="text-muted mb-2 mb-md-0">
                    Menampilkan {{ $users->firstItem() }} - {{ $users->lastItem() }} dari {{ $users->total() }} user
                </div>
                <nav>
                    {{ $users->appends(request()->query())->links('vendor.pagination.bootstrap-5') }}
                </nav>
            </div>
            @endif
            @endif
        </div>
    </div>
</div>

{{-- Include Modals --}}
@include('users.modals.create')
@include('users.modals.edit')

@endsection

@push('styles')
<style>
    .symbol {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        vertical-align: middle;
    }

    .symbol.symbol-circle {
        border-radius: 50%;
    }

    .symbol-40 {
        width: 40px;
        height: 40px;
    }

    .symbol-label {
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
    }

    .btn-icon {
        width: 32px;
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .table th {
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
    }
</style>
@endpush

@push('scripts')
<script>
    $(document).ready(function() {
        // Initialize DataTable
        $('#usersTable').DataTable({
            responsive: true,
            dom: '<"top"f>rt<"bottom"lip><"clear">',
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/id.json'
            },
            columnDefs: [{
                    orderable: false,
                    targets: [0, 4]
                },
                {
                    searchable: false,
                    targets: [0, 3, 4]
                }
            ],
            initComplete: function() {
                $('.dataTables_filter input').addClass('form-control form-control-sm');
                $('.dataTables_length select').addClass('form-control form-control-sm');
            }
        });

        // Edit User Modal
        $('#editUserModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget);
            var modal = $(this);
            var userId = button.data('id');
            var username = button.data('username');
            var name = button.data('name'); // Ambil data nama
            var role = button.data('role');

            modal.find('.modal-title').html(
                `<i class="fas fa-user-edit me-2"></i> Edit User: ${username}`
            );

            // Only populate fields if not opened due to validation error (to preserve old() values from Blade)
            // The modal 'users.modals.edit' already uses old() for field values.
            // We just need to ensure that if it's a fresh open, we populate.
            // If it's a re-open due to validation, old() will handle it.
            if (!modal.find('#edit_username').hasClass('is-invalid') && !modal.find('#edit_role').hasClass('is-invalid')) {
                modal.find('#edit_username').val(username);
                modal.find('#edit_name').val(name); // Set nilai nama di modal edit
                modal.find('#edit_role').val(role);
                modal.find('#edit_password').val(''); // Clear password fields on fresh open
                modal.find('#edit_password_confirmation').val('');
            }
            modal.find('#edit_user_id_input').val(userId); // Set hidden user ID for the form

            // Set form action correctly using Laravel's route helper
            var actionUrl = "{{ route('admin.users.update', ['user' => ':userId']) }}";
            actionUrl = actionUrl.replace(':userId', userId);
            modal.find('form#editUserForm').attr('action', actionUrl);
        });

        // Delete confirmation with SweetAlert
        $('.delete-form').on('submit', function(e) {
            e.preventDefault();
            var form = this;

            Swal.fire({
                title: 'Konfirmasi Hapus User',
                text: "Anda yakin ingin menghapus user ini? Tindakan ini tidak dapat dibatalkan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });

        // Show modals if there are validation errors
        @if($errors->createUser->any())
        $('#createUserModal').modal('show');
        @endif

        @if(session('open_edit_modal') && $errors->editUser->any())
        var userIdToEdit = "{{ session('open_edit_modal') }}";
        var failedEditUsername = "{{ session('failed_edit_username', 'User') }}";
        var modal = $('#editUserModal');
        modal.find('.modal-title').html(
            `<i class="fas fa-user-edit me-2"></i> Edit User: ${failedEditUsername}`
        );
        modal.find('#edit_user_id_input').val(userIdToEdit);
        var actionUrl = "{{ route('admin.users.update', ['user' => ':userId']) }}";
        actionUrl = actionUrl.replace(':userId', userIdToEdit);
        modal.find('form#editUserForm').attr('action', actionUrl);
        modal.modal('show');
        @endif
    });
</script>
@endpush
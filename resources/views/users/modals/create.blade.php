<!-- Modal Tambah User -->
<div class="modal fade" id="createUserModal" tabindex="-1" aria-labelledby="createUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form action="{{ route('admin.users.store') }}" method="POST" id="createUserForm">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createUserModalLabel">Tambah User Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    {{-- Menampilkan error validasi umum dari controller jika ada --}}
                    @if ($errors->createUser->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->createUser->all() as $error)
                            <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    <div class="mb-3">
                        <label for="create_name" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name', 'createUser') is-invalid @enderror" id="create_name" name="name" value="{{ old('name') }}" required>
                        @error('name', 'createUser')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="create_username" class="form-label">Username <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('username', 'createUser') is-invalid @enderror" id="create_username" name="username" value="{{ old('username') }}" required>
                        @error('username', 'createUser')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="create_password" class="form-label">Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control @error('password', 'createUser') is-invalid @enderror" id="create_password" name="password" required>
                        @error('password', 'createUser')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="create_password_confirmation" class="form-label">Konfirmasi Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="create_password_confirmation" name="password_confirmation" required>
                    </div>

                    <div class="mb-3">
                        <label for="create_role" class="form-label">Role <span class="text-danger">*</span></label>
                        <select class="form-select @error('role', 'createUser') is-invalid @enderror" id="create_role" name="role" required>
                            <option value="">Pilih Role...</option>
                            <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                            <option value="user" {{ old('role') == 'user' ? 'selected' : '' }}>User</option>
                        </select>
                        @error('role', 'createUser')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan User</button>
                </div>
            </div>
        </form>
    </div>
</div>
<!-- Modal Edit User -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST" id="editUserForm"> {{-- Action akan di-set oleh JavaScript --}}
            @csrf
            @method('PUT')
            <input type="hidden" name="user_id_for_edit" id="edit_user_id_input">

            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    {{-- Menampilkan error validasi umum dari controller jika ada --}}
                    @if ($errors->editUser->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->editUser->all() as $error)
                            <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name', 'editUser') is-invalid @enderror" id="edit_name" name="name" value="{{ old('name') }}" required>
                        @error('name', 'editUser')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="edit_username" class="form-label">Username <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('username', 'editUser') is-invalid @enderror" id="edit_username" name="username" value="{{ old('username') }}" required>
                        @error('username', 'editUser')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="edit_password" class="form-label">Password Baru (Opsional)</label>
                        <input type="password" class="form-control @error('password', 'editUser') is-invalid @enderror" id="edit_password" name="password">
                        <div class="form-text">Kosongkan jika tidak ingin mengubah password.</div>
                        @error('password', 'editUser')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="edit_password_confirmation" class="form-label">Konfirmasi Password Baru</label>
                        <input type="password" class="form-control" id="edit_password_confirmation" name="password_confirmation">
                    </div>

                    <div class="mb-3">
                        <label for="edit_role" class="form-label">Role <span class="text-danger">*</span></label>
                        <select class="form-select @error('role', 'editUser') is-invalid @enderror" id="edit_role" name="role" required>
                            <option value="">Pilih Role...</option>
                            <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                            <option value="user" {{ old('role') == 'user' ? 'selected' : '' }}>User</option>
                        </select>
                        @error('role', 'editUser')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Update User</button>
                </div>
            </div>
        </form>
    </div>
</div>
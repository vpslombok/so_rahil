<div class="modal fade" id="deleteProductModal" tabindex="-1" aria-labelledby="deleteProductModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                @csrf
                @method('DELETE')
                <input type="hidden" name="user_id_filter" value="{{ request('user_id_filter') }}">
                
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteProductModalLabel">Konfirmasi Penghapusan</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Anda yakin ingin menghapus produk <strong id="delete_product_name"></strong>?</p>
                    <p class="text-danger">Data yang dihapus tidak dapat dikembalikan!</p>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="confirmDelete" required>
                        <label class="form-check-label" for="confirmDelete">
                            Saya yakin ingin menghapus produk ini
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger" id="deleteButton" disabled>
                        <i class="fas fa-trash-alt me-1"></i> Hapus Permanen
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const confirmCheckbox = document.getElementById('confirmDelete');
        const deleteButton = document.getElementById('deleteButton');
        
        if (confirmCheckbox && deleteButton) {
            confirmCheckbox.addEventListener('change', function() {
                deleteButton.disabled = !this.checked;
            });
        }
    });
</script>
@endpush
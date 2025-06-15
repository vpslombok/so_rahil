<div class="modal fade" id="importExcelModal" tabindex="-1" aria-labelledby="importExcelModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('products.import.form') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="importExcelModalLabel">Import Produk dari Excel</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="excel_file" class="form-label">Pilih File Excel</label>
                        <input class="form-control @error('excel_file') is-invalid @enderror" 
                               type="file" id="excel_file" name="excel_file" required 
                               accept=".xlsx,.xls,.csv">
                        @error('excel_file')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="alert alert-info">
                        <h6 class="fw-bold">Petunjuk Import:</h6>
                        <ol class="mb-0">
                            <li>File harus berformat .xlsx, .xls, atau .csv</li>
                            <li>Kolom wajib: barcode, product_code, name</li>
                            <li>Kolom opsional: description, price</li>
                            <li>Baris pertama harus berisi header kolom</li>
                        </ol>
                    </div>
                    
                    <div class="text-center mt-3">
                        <a href="{{ asset('templates/product_import_template.xlsx') }}" 
                           class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-download me-1"></i> Download Template
                        </a>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-info text-white">
                        <i class="fas fa-file-import me-1"></i> Import Data
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
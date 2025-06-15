<!-- Add Product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addProductModalLabel">Tambah Produk Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('products.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Nama Produk <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name', 'createProduct') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                            @error('name', 'createProduct') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="barcode" class="form-label">Barcode <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('barcode', 'createProduct') is-invalid @enderror" id="barcode" name="barcode" value="{{ old('barcode') }}" required>
                            @error('barcode', 'createProduct') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="product_code" class="form-label">Kode Produk</label>
                            <input type="text" class="form-control @error('product_code', 'createProduct') is-invalid @enderror" id="product_code" name="product_code" value="{{ old('product_code') }}">
                            @error('product_code', 'createProduct') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="price" class="form-label">Harga</label>
                            <input type="number" step="0.01" class="form-control @error('price', 'createProduct') is-invalid @enderror" id="price" name="price" value="{{ old('price', 0) }}">
                            @error('price', 'createProduct') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="stock" class="form-label">Stok Awal <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('stock', 'createProduct') is-invalid @enderror" id="stock" name="stock" value="{{ old('stock', 0) }}" required>
                            @error('stock', 'createProduct') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="rack_id" class="form-label">Rak</label>
                            <select class="form-select @error('rack_id', 'createProduct') is-invalid @enderror" id="rack_id" name="rack_id">
                                <option value="">-- Pilih Rak --</option>
                                @if(isset($racks) && $racks->count() > 0)
                                    @foreach($racks as $rack)
                                        <option value="{{ $rack->id }}" {{ old('rack_id') == $rack->id ? 'selected' : '' }}>{{ $rack->name }}</option>
                                    @endforeach
                                @endif
                            </select>
                            @error('rack_id', 'createProduct') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Produk</button>
                </div>
            </form>
        </div>
    </div>
</div>
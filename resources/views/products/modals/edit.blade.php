<!-- Edit Product Modal -->
<div class="modal fade" id="editProductModal" tabindex="-1" aria-labelledby="editProductModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editProductModalLabel">Edit Produk</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editProductForm" method="POST" action=""> {{-- Action will be set by JS --}}
                @csrf
                @method('PUT')
                <input type="hidden" id="edit_product_id" name="product_id"> {{-- Keep this if your JS uses it, though form action has ID --}}

                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_name" class="form-label">Nama Produk <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name', 'editProduct') is-invalid @enderror" id="edit_name" name="name" required>
                            @error('name', 'editProduct') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_barcode" class="form-label">Barcode <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('barcode', 'editProduct') is-invalid @enderror" id="edit_barcode" name="barcode" required>
                            @error('barcode', 'editProduct') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_product_code" class="form-label">Kode Produk</label>
                            <input type="text" class="form-control @error('product_code', 'editProduct') is-invalid @enderror" id="edit_product_code" name="product_code">
                            @error('product_code', 'editProduct') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_price" class="form-label">Harga</label>
                            <input type="number" step="0.01" class="form-control @error('price', 'editProduct') is-invalid @enderror" id="edit_price" name="price">
                            @error('price', 'editProduct') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_stock" class="form-label">Stok <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('stock', 'editProduct') is-invalid @enderror" id="edit_stock" name="stock" required>
                            <small id="edit_stock_user_info" class="form-text text-muted"></small>
                            @error('stock', 'editProduct') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_rack_id" class="form-label">Rak</label>
                            <select class="form-select @error('rack_id', 'editProduct') is-invalid @enderror" id="edit_rack_id" name="rack_id">
                                <option value="">-- Pilih Rak --</option>
                                {{-- Racks options will be populated by ProductController or JS if needed dynamically --}}
                                {{-- Assuming $racks is available from the main view (index.blade.php) --}}
                                @if(isset($racks) && $racks->count() > 0)
                                    @foreach($racks as $rack_item)
                                        <option value="{{ $rack_item->id }}">{{ $rack_item->name }}</option>
                                    @endforeach
                                @endif
                            </select>
                            @error('rack_id', 'editProduct') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Update Produk</button>
                </div>
            </form>
        </div>
    </div>
</div>
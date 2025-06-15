<!-- Select Product Modal -->
<div class="modal fade" id="selectProductModal" tabindex="-1" aria-labelledby="selectProductModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="selectProductModalLabel"><i class="bi bi-box-seam me-2"></i>Pilih Produk</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <input type="text" id="productSearchInput" class="form-control" placeholder="Cari produk berdasarkan Nama, Kode, atau Barcode...">
                </div>
                <div class="table-responsive">
                    <table class="table table-hover" id="productSelectionTable">
                        <thead class="table-light">
                            <tr>
                                <th>Nama Produk</th>
                                <th>Kode Produk</th>
                                <th>Barcode</th>
                                <th>Harga</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(isset($availableProductsForModal) && $availableProductsForModal->count() > 0)
                            @foreach($availableProductsForModal as $product)
                            <tr class="product-row" data-product-identifier="{{ $product->product_code ?: $product->barcode }}" style="cursor: pointer;">
                                <td>{{ $product->name }}</td>
                                <td>{{ $product->product_code ?: '-' }}</td>
                                <td>{{ $product->barcode ?: '-' }}</td>
                                <td>Rp {{ number_format($product->price, 0, ',', '.') }}</td>
                            </tr>
                            @endforeach
                            @else
                            <tr>
                                <td colspan="4" class="text-center text-muted">Tidak ada produk yang tersedia.</td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
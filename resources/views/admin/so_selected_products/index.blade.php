@extends('layouts.app')

@section('title', 'Kelola Produk untuk SO: ' . $so_event->name)

@section('content')
<div class="container-fluid px-4">
    <!-- Header Section -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-2">
                <a href="{{ route('admin.so-events.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i>
                </a>
            </div>
            <h1 class="h3 mb-1">Kelola Produk untuk <span class="text-primary">{{ $so_event->name }}</span></h1>
            <p class="text-muted mb-0">Tambah atau hapus produk yang akan di-opname untuk event ini</p>
        </div>
    </div>

    @include('layouts.flash-messages')

    <!-- Main Content Cards -->
    <div class="row">
        <!-- Add Product Card -->
        <div class="col-lg-6 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="mb-0 d-flex align-items-center">
                        <i class="bi bi-plus-circle-fill text-primary me-2"></i>
                        Tambah Produk
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.so-events.products.store', $so_event->id) }}" method="POST" id="addProductForm">
                        @csrf
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Tambah Berdasarkan:</label>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="selection_mode" id="modeProduct" value="product" {{ old('selection_mode', 'product') == 'product' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="modeProduct">
                                        <i class="bi bi-upc-scan me-1"></i> Produk Individual
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="selection_mode" id="modeShelf" value="shelf" {{ old('selection_mode') == 'shelf' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="modeShelf">
                                        <i class="bi bi-collection me-1"></i> Seluruh Rak
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Product Input Section -->
                        <div id="productInputSection">
                            <div class="mb-3">
                                <label for="product_identifier" class="form-label fw-semibold">Cari Produk</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-upc-scan"></i></span>
                                    <input type="text" name="product_identifier" id="product_identifier" class="form-control @error('product_identifier') is-invalid @enderror" value="{{ old('product_identifier') }}" placeholder="Kode/Barcode Produk">
                                    <button class="btn btn-outline-primary" type="button" id="browseProductListButton" data-bs-toggle="modal" data-bs-target="#selectProductModal">
                                        <i class="bi bi-search"></i> Cari
                                    </button>
                                </div>
                                @error('product_identifier')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-plus-lg me-1"></i> Tambahkan Produk
                            </button>
                        </div>

                        <!-- Shelf Input Section -->
                        <div id="shelfInputSection" style="display: none;">
                            <div class="mb-3">
                                <label for="shelf_identifier" class="form-label fw-semibold">Pilih Rak</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-grid-3x3-gap"></i></span>
                                    <input type="text" name="shelf_identifier" id="shelf_identifier" class="form-control @error('shelf_identifier') is-invalid @enderror" value="{{ old('shelf_identifier') }}" placeholder="Kode/Nama Rak">
                                    <button class="btn btn-outline-primary" type="button" id="browseShelfListButton" data-bs-toggle="modal" data-bs-target="#selectShelfModal">
                                        <i class="bi bi-search"></i> Cari
                                    </button>
                                </div>
                                @error('shelf_identifier')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <button type="submit" class="btn btn-info w-100">
                                <i class="bi bi-collection me-1"></i> Tambahkan Semua Produk
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Stats Card -->
        <div class="col-lg-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="mb-0 d-flex align-items-center">
                        <i class="bi bi-graph-up text-primary me-2"></i>
                        Statistik Event
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="border rounded p-3 text-center">
                                <h3 class="text-primary mb-1">{{ $selectedProducts->total() ?? 0 }}</h3>
                                <p class="text-muted mb-0">Total Produk</p>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="border rounded p-3 text-center">
                                <h3 class="text-success mb-1">{{ $completedCount ?? 0 }}</h3>
                                <p class="text-muted mb-0">Telah Diopname</p>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="border rounded p-3 text-center">
                                <h3 class="text-warning mb-1">{{ $pendingCount ?? 0 }}</h3>
                                <p class="text-muted mb-0">Belum Diopname</p>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="border rounded p-3 text-center">
                                <h3 class="text-info mb-1">{{ $rackCount ?? 0 }}</h3>
                                <p class="text-muted mb-0">Rak Terlibat</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Product List Card -->
    <div class="card border-0 shadow-sm mt-2">
        <div class="card-header bg-white border-bottom py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0 d-flex align-items-center">
                    <i class="bi bi-list-check text-primary me-2"></i>
                    Daftar Produk Terpilih
                </h5>
                @if(isset($selectedProducts) && $selectedProducts->isNotEmpty())
                <button type="button" id="deleteSelectedButton" class="btn btn-danger btn-sm" disabled>
                    <i class="bi bi-trash-fill me-1"></i> Hapus Terpilih
                </button>
                @endif
            </div>
        </div>
        <div class="card-body">
            @if(isset($selectedProducts) && $selectedProducts->isNotEmpty())
            <div class="d-flex justify-content-start align-items-center mb-3">
                <form method="GET" action="{{ route('admin.so-events.show', $so_event->id) }}" id="perPageFormSelectedProducts" class="d-flex align-items-center">
                    <label for="per_page_select_sp" class="form-label me-2 mb-0">Tampilkan:</label>
                    <select name="per_page" id="per_page_select_sp" class="form-select form-select-sm" style="width: auto;" onchange="document.getElementById('perPageFormSelectedProducts').submit();">
                        <option value="5" {{ ($perPageSelected ?? 5) == 5 ? 'selected' : '' }}>5</option>
                        <option value="25" {{ ($perPageSelected ?? 5) == 25 ? 'selected' : '' }}>25</option>
                        <option value="50" {{ ($perPageSelected ?? 5) == 50 ? 'selected' : '' }}>50</option>
                        <option value="100" {{ ($perPageSelected ?? 5) == 100 ? 'selected' : '' }}>100</option>
                    </select>
                    <span class="ms-2 text-muted">item per halaman</span>
                     {{-- Preserve other query parameters if any (though not strictly needed here as form action is specific) --}}
                    @foreach(request()->except(['page', 'per_page']) as $key => $value)
                        @if(is_array($value))
                            @foreach($value as $sub_value)
                                <input type="hidden" name="{{ $key }}[]" value="{{ $sub_value }}">
                            @endforeach
                        @else
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endif
                    @endforeach
                </form>
            </div>
            @endif
            @if(!isset($selectedProducts) || $selectedProducts->isEmpty())
            <div class="text-center py-5">
                <i class="bi bi-box-seam display-5 text-muted mb-3"></i>
                <h5 class="text-muted">Belum ada produk yang dipilih</h5>
                <p class="text-muted">Tambahkan produk menggunakan form di atas</p>
            </div>
            @else
            <form id="bulkDeleteForm" action="{{ route('admin.so_selected_products.bulkDestroy') }}" method="POST">
                @csrf
                @method('DELETE')

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th width="40">
                                    <input type="checkbox" id="selectAllCheckbox" class="form-check-input">
                                </th>
                                <th>No</th>
                                <th>Produk</th>
                                <th>Status</th>
                                <th>Ditambahkan</th>
                                <th width="120">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($selectedProducts as $index => $item)
                            <tr>
                                <td>
                                    <input type="checkbox" name="selected_ids[]" value="{{ $item->id }}" class="form-check-input row-checkbox">
                                </td>
                                <td>{{ $selectedProducts->firstItem() + $index }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0 bg-light rounded p-2 me-2">
                                            <i class="bi bi-box-seam text-primary"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0">{{ $item->product->name ?? 'Produk Tidak Ditemukan' }}</h6>
                                            @php
                                                $code = $item->product->product_code ?? null;
                                                $barcode = $item->product->barcode ?? null;
                                                $displayIdentifier = 'N/A';

                                                if ($code && $barcode) {
                                                    $displayIdentifier = $code . ' / ' . $barcode;
                                                } elseif ($code) {
                                                    $displayIdentifier = $code;
                                                } elseif ($barcode) {
                                                    $displayIdentifier = $barcode;
                                                }
                                            @endphp
                                            <small class="text-muted">{{ $displayIdentifier }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $item->status === 'completed' ? 'success' : 'warning' }}">
                                        {{ $item->status === 'completed' ? 'Selesai' : 'Belum' }}
                                    </span>
                                </td>
                                <td>
                                    <small class="text-muted">{{ $item->created_at->format('d M Y') }}</small><br>
                                    <small>oleh {{ $item->addedBy->username ?? 'N/A' }}</small>
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <a href="#" class="btn btn-sm btn-outline-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('admin.so_selected_products.destroy', $item->id) }}" method="POST" class="delete-form">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($selectedProducts->hasPages())
                <div class="mt-3 d-flex justify-content-center">
                    {{ $selectedProducts->links('vendor.pagination.bootstrap-5') }}
                </div>
                @endif
            </form>
            @endif
        </div>
    </div>
</div>

<!-- Modals (same as before) -->
@include('admin.so_selected_products.partials.select_product_modal')
@include('admin.so_selected_products.partials.select_shelf_modal')

@endsection

@push('styles')
<style>
    .card {
        border-radius: 12px;
    }

    .card-header {
        border-radius: 12px 12px 0 0 !important;
    }

    .table {
        --bs-table-hover-bg: rgba(13, 110, 253, 0.05);
    }

    .product-row:hover,
    .shelf-row:hover {
        background-color: rgba(13, 110, 253, 0.1);
    }

    .form-check-input:checked {
        background-color: #0d6efd;
        border-color: #0d6efd;
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const modalElement = document.getElementById('selectProductModal');
        let productModalInstance;
        if (modalElement) {
            productModalInstance = new bootstrap.Modal(modalElement);
        }

        const shelfModalElement = document.getElementById('selectShelfModal');
        let shelfModalInstance;
        if (shelfModalElement) {
            shelfModalInstance = new bootstrap.Modal(shelfModalElement);
        }

        const addProductForm = document.getElementById('addProductForm');
        const modeProductRadio = document.getElementById('modeProduct');
        const modeShelfRadio = document.getElementById('modeShelf');
        const productInputSection = document.getElementById('productInputSection');
        const shelfInputSection = document.getElementById('shelfInputSection');
        const productIdentifierInput = document.getElementById('product_identifier');
        const shelfIdentifierInput = document.getElementById('shelf_identifier');

        function toggleInputSections() {
            if (!modeProductRadio || !modeShelfRadio || !productInputSection || !shelfInputSection || !productIdentifierInput || !shelfIdentifierInput) return;

            if (modeProductRadio.checked) {
                productInputSection.style.display = '';
                shelfInputSection.style.display = 'none';
                productIdentifierInput.setAttribute('required', 'required');
                shelfIdentifierInput.removeAttribute('required');
            } else if (modeShelfRadio.checked) {
                productInputSection.style.display = 'none';
                shelfInputSection.style.display = '';
                shelfIdentifierInput.setAttribute('required', 'required');
                productIdentifierInput.removeAttribute('required');
            }
        }

        if (modeProductRadio) modeProductRadio.addEventListener('change', toggleInputSections);
        if (modeShelfRadio) modeShelfRadio.addEventListener('change', toggleInputSections);

        // Initial state based on old input or default
        const oldSelectionMode = "{{ old('selection_mode', 'product') }}";
        if (oldSelectionMode === 'shelf' && modeShelfRadio) {
            modeShelfRadio.checked = true;
        } else if (modeProductRadio) {
            modeProductRadio.checked = true;
        }
        toggleInputSections();

        const browseProductListButton = document.getElementById('browseProductListButton');
        if (browseProductListButton && productModalInstance) {
            browseProductListButton.addEventListener('click', () => {
                productModalInstance.show();
            });
        }

        document.querySelectorAll('.product-row').forEach(rowEl => {
            rowEl.style.cursor = 'pointer';
            rowEl.addEventListener('click', () => {
                const identifier = rowEl.dataset.productIdentifier;
                if (identifier && productIdentifierInput && addProductForm && productModalInstance) {
                    productIdentifierInput.value = identifier;
                    if (modeProductRadio) modeProductRadio.checked = true;
                    toggleInputSections();
                    // Add listener to submit form once modal is hidden
                    modalElement.addEventListener('hidden.bs.modal', () => {
                        addProductForm.submit();
                    }, {
                        once: true
                    });
                    productModalInstance.hide();
                }
            });
        });

        const productSearchInput = document.getElementById('productSearchInput');
        if (productSearchInput && modalElement) {
            productSearchInput.addEventListener('keyup', function() {
                const search = this.value.toLowerCase();
                document.querySelectorAll('#productSelectionTable tbody tr').forEach(rowEl => {
                    const match = rowEl.textContent.toLowerCase().includes(search);
                    rowEl.style.display = match ? '' : 'none';
                });
            });
            modalElement.addEventListener('hidden.bs.modal', () => {
                productSearchInput.value = ''; // Clear search on modal close
                document.querySelectorAll('#productSelectionTable tbody tr').forEach(rowEl => rowEl.style.display = '');
            });
        }

        // Shelf Modal Logic
        const browseShelfListButton = document.getElementById('browseShelfListButton');
        if (browseShelfListButton && shelfModalInstance) {
            browseShelfListButton.addEventListener('click', () => shelfModalInstance.show());
        }

        document.querySelectorAll('.shelf-row').forEach(rowEl => {
            rowEl.style.cursor = 'pointer';
            rowEl.addEventListener('click', () => {
                const identifier = rowEl.dataset.shelfIdentifier;
                if (identifier && shelfIdentifierInput && addProductForm && shelfModalInstance) {
                    shelfIdentifierInput.value = identifier;
                    if (modeShelfRadio) modeShelfRadio.checked = true;
                    toggleInputSections();
                    shelfModalElement.addEventListener('hidden.bs.modal', () => {
                        addProductForm.submit();
                    }, {
                        once: true
                    });
                    shelfModalInstance.hide();
                }
            });
        });

        const shelfSearchInput = document.getElementById('shelfSearchInput');
        if (shelfSearchInput && shelfModalElement) {
            shelfSearchInput.addEventListener('keyup', function() {
                const search = this.value.toLowerCase();
                document.querySelectorAll('#shelfSelectionTable tbody tr').forEach(rowEl => {
                    const match = rowEl.textContent.toLowerCase().includes(search);
                    rowEl.style.display = match ? '' : 'none';
                });
            });
            shelfModalElement.addEventListener('hidden.bs.modal', () => {
                shelfSearchInput.value = ''; // Clear search on modal close
                document.querySelectorAll('#shelfSelectionTable tbody tr').forEach(rowEl => rowEl.style.display = '');
            });
        }

        // --- Bulk Delete Logic for Selected Products ---
        const bulkDeleteForm = document.getElementById('bulkDeleteForm');
        const deleteSelectedButton = document.getElementById('deleteSelectedButton');
        const selectAllCheckbox = document.getElementById('selectAllCheckbox');
        const rowCheckboxesNodeList = document.querySelectorAll('.row-checkbox');

        if (bulkDeleteForm && deleteSelectedButton && selectAllCheckbox && rowCheckboxesNodeList.length > 0) {
            const rowCheckboxes = Array.from(rowCheckboxesNodeList);

            const toggleDeleteButtonState = () => {
                const anyChecked = rowCheckboxes.some(checkbox => checkbox.checked);
                deleteSelectedButton.disabled = !anyChecked;
            };

            selectAllCheckbox.addEventListener('change', function() {
                rowCheckboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
                toggleDeleteButtonState();
            });

            rowCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', () => {
                    if (!checkbox.checked) {
                        selectAllCheckbox.checked = false;
                    } else {
                        const allChecked = rowCheckboxes.every(cb => cb.checked);
                        selectAllCheckbox.checked = allChecked;
                    }
                    toggleDeleteButtonState();
                });
            });

            deleteSelectedButton.addEventListener('click', function() {
                const checkedCount = rowCheckboxes.filter(checkbox => checkbox.checked).length;

                if (checkedCount > 0) {
                    Swal.fire({
                        title: 'Apakah Anda yakin?',
                        text: `Anda akan menghapus ${checkedCount} produk dari SO Event ini`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Ya, hapus!',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            bulkDeleteForm.submit();
                        }
                    });
                } else {
                    Swal.fire({
                        title: 'Peringatan',
                        text: 'Pilih setidaknya satu produk untuk dihapus',
                        icon: 'warning',
                        confirmButtonColor: '#3085d6',
                    });
                }
            });

            toggleDeleteButtonState(); // Initial check
        }
    });
</script>
@endpush
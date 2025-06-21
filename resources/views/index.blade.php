@extends('layouts.app')

@section('title', 'Dashboard Produk')

@section('content')
<div class="container-fluid">
    {{-- Page Header --}}
    <div class="text-center pt-2 pb-2 mb-3 border-bottom">
        <h1 class="h2 d-inline-block mb-0 me-2 align-middle">Manajemen Stok</h1>
        @if($products_data->total() > 0)
        <span class="badge bg-light text-dark border align-middle">
            <small>Total: {{ $products_data->total() }}</small>
        </span>
        @endif
    </div>

    {{-- Flash Messages --}}
    @include('layouts.flash-messages')

    {{-- Controls: Filter (Admin) & Add Product Button --}}
    @auth {{-- Pastikan user login untuk melihat kontrol ini --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body p-3"> {{-- Padding konsisten --}}
            <div class="row g-3 align-items-end"> {{-- Gutter lebih besar, align-items-end untuk form --}}
                {{-- Filter, Pencarian Event, dan Pencarian Produk dalam satu form --}}
                <div class="col-lg">
                    <form method="GET" action="{{ route('dashboard') }}" id="filterAndSearchForm" class="row gx-2 gy-2 align-items-end">
                        @if(Auth::user()->role == 'admin' && $users_for_filter->isNotEmpty()) {{-- Filter User (Admin Only) --}}
                        <div class="col-md-auto">
                            <label for="user_id_filter" class="form-label form-label-sm fw-semibold">User</label>
                            <select name="user_id_filter" id="user_id_filter" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="">Semua</option>
                                @foreach($users_for_filter as $user)
                                <option value="{{ $user->id }}" {{ $selectedUserId == $user->id ? 'selected' : '' }}>
                                    {{ $user->username }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                        {{-- Filter Rak --}}
                        <div class="col-md-auto">
                            <label for="rack_id_filter" class="form-label form-label-sm fw-semibold">Rak</label>
                            <select name="rack_id_filter" id="rack_id_filter" class="form-select form-select-sm" onchange="this.form.submit()" title="Filter berdasarkan Rak">
                                <option value="">Semua Rak</option>
                                @if(isset($racks) && $racks->count() > 0)
                                @foreach($racks as $rack_item_filter)
                                <option value="{{ $rack_item_filter->id }}" {{ (isset($selectedRackId) && $selectedRackId == $rack_item_filter->id) ? 'selected' : '' }}>
                                    {{ $rack_item_filter->name }}
                                </option>
                                @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="col-md-auto">
                            <label for="event_id_filter" class="form-label form-label-sm fw-semibold">Filter Event SO</label>
                            <select name="event_id_filter" id="event_id_filter" class="form-select form-select-sm" onchange="this.form.submit()" title="Filter berdasarkan SO Item Rahil">
                                <option value="">Tampilkan Semua Produk</option>
                                @foreach($activeSoEvents as $event)
                                <option value="{{ $event->id }}" {{ (isset($selectedEventId) && $selectedEventId == $event->id) ? 'selected' : '' }}>
                                    Produk untuk: {{ $event->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Search Product (Visible to all authenticated users) --}}
                        <div class="col-md">
                            <label for="search_product" class="form-label form-label-sm fw-semibold">Cari Produk</label>
                            <div class="input-group input-group-sm">
                                <input type="text" name="search_product" id="search_product" class="form-control" placeholder="Kode, barcode, atau nama produk..." value="{{ $searchQuery ?? '' }}">
                                <button type="submit" class="btn btn-outline-secondary">
                                    <i class="bi bi-search"></i> <span class="d-none d-sm-inline">Cari</span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                {{-- Action Buttons (Admin Only) --}}
                @if(Auth::user()->role == 'admin')
                <div class="col-lg-auto mt-2 mt-lg-0">
                    <div class="d-flex justify-content-start justify-content-lg-end">
                        <div class="btn-group btn-group-sm" role="group" aria-label="Aksi Produk">
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
                                <i class="bi bi-plus-circle me-1"></i> <span class="d-none d-md-inline">Tambah Produk</span>
                            </button>
                            <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#importExcelModal">
                                <i class="bi bi-file-earmark-excel me-1"></i> <span class="d-none d-md-inline">Import Excel</span>
                            </button>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endauth

        {{-- Products Table --}}
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="table-responsive mb-4">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th class="d-none d-sm-table-cell">Kode</th>
                                <th class="d-none d-sm-table-cell">Barcode</th>
                                <th>Nama Produk</th>
                                <th class="d-none d-lg-table-cell">Rak</th> {{-- Added Rack Column --}}
                                <th>Harga</th>
                                <th>Stok</th>
                                <th class="d-none d-sm-table-cell">Update Terakhir</th>
                                @if(Auth::user()->role == 'admin')
                                <th>Menu</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($products_data as $product)
                            <tr @if(Auth::user()->role != 'admin') class="clickable-row" data-bs-toggle="modal"
                                data-product="{{ json_encode([ // Keep data-product
                        'id' => $product->id,
                        'product_code' => $product->product_code,
                        'barcode' => $product->barcode,
                        'name' => $product->name,
                        'price' => $product->price,
                        'stock' => $product->userStocks->first()->stock ?? 0,
                        'rack_id' => $product->rack_id // Add rack_id to data-product
                    ]) }}" @endif>
                                <td>{{ $products_data->firstItem() + $loop->index }}</td>
                                <td class="d-none d-sm-table-cell">{{ $product->product_code ?? '-' }}</td>
                                <td class="d-none d-sm-table-cell">{{ $product->barcode ?? '-' }}</td>
                                <td>{{ $product->name }}</td>
                                <td class="d-none d-lg-table-cell">{{ $product->rack->name ?? '-' }}</td> {{-- Display Rack Name --}}
                                <td>Rp {{ number_format($product->price ?? 0, 0, ',', '.') }}</td>
                                @php
                                $displayStockValue = 0;
                                $isStockTotalForAdmin = false;

                                // Cek apakah admin sedang melihat "Semua User"
                                if (Auth::user()->role == 'admin' && empty($selectedUserId)) {
                                $displayStockValue = $product->userStocks->sum('stock');
                                $isStockTotalForAdmin = true;
                                } else {
                                // Admin memilih user spesifik, atau user non-admin (melihat stok sendiri)
                                // $product->userStocks sudah terfilter untuk user spesifik oleh controller
                                $displayStockValue = $product->userStocks->first()->stock ?? 0;
                                }
                                @endphp
                                <td class="{{ $displayStockValue <= 0 ? 'text-danger fw-bold' : '' }}">
                                    {{ $displayStockValue }}
                                    @if($isStockTotalForAdmin)
                                    <small class="text-muted" style="font-size: 0.8em;">(Total)</small>
                                    @endif
                                </td>
                                <td class="d-none d-sm-table-cell">
                                    @if(Auth::user()->role == 'admin')
                                    {{-- Admin melihat updated_at dari produk --}}
                                    {{ optional($product->updated_at)->format('d/m/Y H:i') ?? '-' }}
                                    @else
                                    {{-- User melihat updated_at dari stok user_product_stock mereka untuk produk ini --}}
                                    {{ optional($product->userStocks->first()?->updated_at)->format('d/m/Y H:i') ?? '-' }}
                                    @endif
                                </td>
                                @if(Auth::user()->role == 'admin')
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="actionsDropdown{{ $product->id }}" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="bi bi-three-dots"></i>
                                        </button>
                                        <ul class="dropdown-menu" aria-labelledby="actionsDropdown{{ $product->id }}">
                                            <li>
                                                <button class="dropdown-item edit-product-btn" type="button"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editProductModal"
                                                    data-product="{{ json_encode([
                                            'id' => $product->id,
                                            'product_code' => $product->product_code,
                                            'barcode' => $product->barcode,
                                            'name' => $product->name,
                                            'price' => $product->price,
                                            'stock' => $product->userStocks->first()->stock ?? 0,
                                            'rack_id' => $product->rack_id // Add rack_id here too
                                        ]) }}">
                                                    <i class="bi bi-pencil me-2"></i>Edit
                                                </button>
                                            </li>
                                            <li>
                                                <button class="dropdown-item delete-product-btn text-danger" type="button"
                                                    data-id="{{ $product->id }}"
                                                    data-name="{{ $product->name }}">
                                                    <i class="bi bi-trash me-2"></i>Hapus
                                                </button>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                                @endif
                            </tr>
                            @empty
                            <tr>
                                <td colspan="{{ Auth::user()->role == 'admin' ? 9 : 8 }}" class="text-center">Tidak ada data produk</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                    {{-- Pagination --}}
                    @if($products_data->hasPages())
                    <div class="d-flex justify-content-between align-items-center flex-wrap mt-3">
                        <div>
                            <span class="text-muted">
                                Menampilkan {{ $products_data->firstItem() }} - {{ $products_data->lastItem() }} dari {{ $products_data->total() }} produk
                            </span>
                        </div>
                        <div>
                            {{ $products_data->appends(request()->query())->onEachSide(1)->links('vendor.pagination.bootstrap-5') }}
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>



        {{-- Modals --}}
        @include('products.modals.create')
        @include('products.modals.import')
        @include('products.modals.edit')
        @include('products.modals.delete')
    </div>
    @endsection

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize filter form submission
            document.getElementById('user_id_filter')?.addEventListener('change', function() {
                this.form.submit();
            });

            document.getElementById('event_id_filter')?.addEventListener('change', function() {
                this.form.submit();
            });

            document.getElementById('rack_id_filter')?.addEventListener('change', function() {
                this.form.submit();
            });

            // Make table rows clickable for non-admin users
            const clickableRows = document.querySelectorAll('.clickable-row');
            const editModalElement = document.getElementById('editProductModal');
            const editModalInstance = editModalElement ? new bootstrap.Modal(editModalElement) : null;

            if (clickableRows.length > 0 && editModalInstance) {
                clickableRows.forEach(row => {
                    row.style.cursor = 'pointer'; // Add visual cue
                    row.addEventListener('click', function() {
                        // Trigger the modal, passing the row as the relatedTarget
                        editModalInstance.show(this);
                    });
                });
            }

            // Edit Product Modal Handling
            const editModal = document.getElementById('editProductModal');
            if (editModal) {
                editModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    const productData = JSON.parse(button.getAttribute('data-product'));
                    const isAdmin = "{{ Auth::user()->role == 'admin' }}";
                    const selectedUserIdFilter = document.getElementById('user_id_filter')?.value;
                    const selectedEventIdFilter = document.getElementById('event_id_filter')?.value; // Ambil event_id
                    const selectedRackIdFilter = document.getElementById('rack_id_filter')?.value || ''; // Ambil rack_id

                    // Set form fields
                    this.querySelector('#edit_product_id').value = productData.id;
                    this.querySelector('#edit_product_code').value = productData.product_code || '';
                    this.querySelector('#edit_barcode').value = productData.barcode || '';
                    this.querySelector('#edit_name').value = productData.name || '';
                    this.querySelector('#edit_rack_id').value = productData.rack_id || ''; // Set rack_id for edit modal
                    const rackFieldContainer = this.querySelector('#edit_rack_id').closest('.mb-3'); // Asumsi input rak dibungkus div.mb-3
                    const priceFieldContainer = this.querySelector('#edit_price')?.closest('.mb-3'); // Asumsi input harga dibungkus div.mb-3

                    const editPriceEl = this.querySelector('#edit_price');
                    if (editPriceEl) {
                        editPriceEl.value = productData.price || 0;
                    }

                    const stockInput = this.querySelector('#edit_stock');
                    stockInput.value = productData.stock || 0;
                    const stockFieldContainer = stockInput.closest('.mb-3'); // Asumsi input stok dibungkus div.mb-3
                    const stockUserInfo = this.querySelector('#edit_stock_user_info');

                    // Hide stock input field and its label if admin is viewing "Semua User"
                    // Atau jika SO Event tidak dipilih (artinya tidak dalam konteks SO spesifik)
                    if (isAdmin === "1" && (selectedUserIdFilter === "" || selectedUserIdFilter === null || typeof selectedUserIdFilter === 'undefined')) {
                        if (stockFieldContainer) {
                            stockFieldContainer.classList.add('d-none');
                        }
                        if (stockUserInfo) stockUserInfo.textContent = ''; // Kosongkan info jika field disembunyikan
                        stockInput.disabled = true;
                    } else {
                        if (stockFieldContainer) {
                            stockFieldContainer.classList.remove('d-none');
                        }
                        stockInput.disabled = false;
                        if (stockUserInfo) {
                            if (isAdmin === "1" && selectedUserIdFilter) {
                                const selectedUserText = document.getElementById('user_id_filter').options[document.getElementById('user_id_filter').selectedIndex].text;
                                stockUserInfo.textContent = `Stok untuk user: ${selectedUserText}`;
                            } else if (isAdmin !== "1") {
                                stockUserInfo.textContent = 'Stok Anda saat ini.';
                            } else {
                                stockUserInfo.textContent = ''; // Kosongkan jika tidak ada konteks user spesifik
                            }
                        }
                    }

                    // Set form action (base URL, filter parameters akan dikirim via hidden inputs)
                    this.querySelector('form').action = `/products/${productData.id}?user_id_filter=${selectedUserIdFilter}&event_id_filter=${selectedEventIdFilter}&rack_id_filter=${selectedRackIdFilter}`;

                    // Toggle fields based on role
                    const isNotAdmin = !(isAdmin === "1");

                    // Sembunyikan field Rak untuk non-admin
                    if (rackFieldContainer) {
                        if (isNotAdmin) {
                            rackFieldContainer.classList.add('d-none');
                        } else {
                            rackFieldContainer.classList.remove('d-none');
                        }
                    }

                    // Sembunyikan field Harga untuk non-admin
                    if (priceFieldContainer) {
                        if (isNotAdmin) {
                            priceFieldContainer.classList.add('d-none');
                        } else {
                            priceFieldContainer.classList.remove('d-none');
                        }
                    }

                    ['edit_product_code', 'edit_barcode', 'edit_name', 'edit_price'].forEach(fieldId => {
                        const el = this.querySelector(`#${fieldId}`);
                        if (el) el.disabled = isNotAdmin;
                    });

                    // Focus on stock input if it's not hidden
                    if (stockFieldContainer && !stockFieldContainer.classList.contains('d-none')) {
                        stockInput.focus();
                    }
                });
            }

            // Delete Confirmation
            document.querySelectorAll('.delete-product-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const productId = this.getAttribute('data-id');
                    const productName = this.getAttribute('data-name');

                    const deleteModal = document.getElementById('deleteProductModal');
                    if (deleteModal) {
                        deleteModal.querySelector('#delete_product_name').textContent = productName;
                        deleteModal.querySelector('form').action = `/products/${productId}`;

                        // Add filter parameter if exists
                        const queryParamsDelete = new URLSearchParams();
                        const userIdFilter = document.getElementById('user_id_filter')?.value;
                        if (userIdFilter) {
                            queryParamsDelete.append('user_id_filter', userIdFilter);
                        }
                        const eventIdFilter = document.getElementById('event_id_filter')?.value;
                        if (eventIdFilter) {
                            queryParamsDelete.append('event_id_filter', eventIdFilter);
                        }
                        const rackIdFilter = document.getElementById('rack_id_filter')?.value;
                        if (rackIdFilter) {
                            queryParamsDelete.append('rack_id_filter', rackIdFilter);
                        }
                        if (queryParamsDelete.toString()) {
                            deleteModal.querySelector('form').action += `?${queryParamsDelete.toString()}`;
                        }

                        new bootstrap.Modal(deleteModal).show();
                    }
                });
            });
        });
    </script>
    @endpush
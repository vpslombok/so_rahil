@extends('layouts.app')

@section('title', 'Entry Stok Fisik untuk SO: ' . ($currentEvent->name ?? 'Tidak Diketahui') . ($displayNomorNota ? ' (Nota: ' . $displayNomorNota . ')' : ''))

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">
        Entry Stok Fisik: <span class="text-primary">{{ $currentEvent->name ?? 'SO Tidak Dipilih' }}</span>
        @if($displayNomorNota)
        <small class="text-muted">(No. Nota: {{ $displayNomorNota }})</small>
        @endif
    </h1>

    @include('layouts.flash-messages')

    {{-- Input untuk pencarian barcode/kode produk --}}
    @if(isset($currentEvent) && $currentEvent && isset($productsForEntry) && !$productsForEntry->isEmpty())
    <div class="row mb-3">
        <div class="col-md-6">
            <div class="input-group">
                <input type="text" id="barcode_search_input" class="form-control" placeholder="Scan Barcode atau ketik Kode Produk...">
                <button class="btn btn-primary" type="button" id="search_product_button"><i class="bi bi-search"></i> Cari</button>
            </div>
            <small class="form-text text-muted">Tekan Enter setelah input atau klik tombol Cari.</small>
        </div>
    </div>
    @endif


    @if(isset($currentEvent) && $currentEvent)
    <form method="POST" action="{{ route('stock_check.store') }}">
        @csrf
        <input type="hidden" name="so_event_id" value="{{ $currentEvent->id }}">
        @if(isset($displayNomorNota))
        <input type="hidden" name="nomor_nota" value="{{ $displayNomorNota }}">
        @endif
        {{-- Anda bisa menambahkan nomor nota sebagai hidden input jika diperlukan saat submit --}}

        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Daftar Produk</h6>
                @if(isset($productsForEntry) && !$productsForEntry->isEmpty())
                <button type="submit" class="btn btn-success btn-sm">
                    <i class="bi bi-save"></i> Simpan Semua Entri
                </button>
                @endif
            </div>
            <div class="card-body">
                @if(!isset($productsForEntry) || $productsForEntry->isEmpty())
                <p class="text-center">Tidak ada produk yang terdaftar untuk SO ini atau semua produk sudah di-entry pada halaman sebelumnya.</p>
                <p class="text-center">Jika Anda baru saja memilih SO, pastikan SO tersebut memiliki produk yang telah ditentukan.</p>
                @else
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="entryStockTable">
                        <thead>
                            <tr>
                                <th style="width: 5%;">No</th>
                                <th style="width: 15%;">Kode Produk</th>
                                <th style="width: 20%;">Barcode</th>
                                <th>Nama Produk</th>
                                {{-- <th style="width: 10%;">Stok Sistem (saat SO disiapkan)</th> --}}
                                <th style="width: 15%;" class="text-center">Stok Fisik (Input)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($productsForEntry as $index => $item)
                            <tr>
                                <td>{{ $productsForEntry->firstItem() + $index }}</td>
                                <td data-product-code="{{ $item->product->product_code ?? '' }}">{{ $item->product->product_code ?? 'N/A' }}</td>
                                <td data-product-barcode="{{ $item->product->barcode ?? '' }}">{{ $item->product->barcode ?? 'N/A' }}</td>
                                <td>{{ $item->product->name ?? 'Produk Tidak Ditemukan' }}</td>
                                {{-- <td class="text-center">{{ $item->system_stock ?? 0 }}</td> --}}
                                <td>
                                    {{-- $item->id di sini adalah ID dari TempStockEntry --}}
                                    <input type="hidden" name="products[{{ $item->id }}][temp_stock_entry_id]" value="{{ $item->id }}">
                                    <input type="number"
                                        name="products[{{ $item->id }}][recorded_stock]"
                                        class="form-control form-control-sm @error('products.'.$item->id.'.recorded_stock') is-invalid @enderror"
                                        value="{{ old('products.'.$item->id.'.recorded_stock', $item->physical_stock ?? '0') }}"
                                        min="0"
                                        required
                                        placeholder="Qty">
                                    @error('products.'.$item->id.'.recorded_stock')
                                    <div class="invalid-feedback d-block">
                                        {{ $message }}
                                    </div>
                                    @enderror
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($productsForEntry->hasPages())
                <div class="d-flex justify-content-between align-items-center flex-wrap mt-3">
                    <div>
                        <span class="text-muted">
                            Menampilkan {{ $productsForEntry->firstItem() }} - {{ $productsForEntry->lastItem() }} dari {{ $productsForEntry->total() }} produk
                        </span>
                    </div>
                    <div>
                        {{ $productsForEntry->appends(request()->except('page'))->links('vendor.pagination.bootstrap-5') }}
                    </div>
                </div>
                @endif
                @endif
            </div>
        </div>

        @if(isset($productsForEntry) && !$productsForEntry->isEmpty())
        <div class="mt-3 mb-4 text-center">
            <button type="submit" class="btn btn-success btn-lg">
                <i class="bi bi-save"></i> Simpan Semua Entri Stok Fisik
            </button>
            @endif
            <a href="{{ route('so_by_selected.index') }}" class="btn btn-secondary ms-2">
                <i class="bi bi-arrow-left-circle"></i> Kembali ke Pemilihan SO
            </a>
        </div>
    </form>
    @else
    <div class="alert alert-warning text-center">
        SO Event tidak ditemukan atau belum dipilih. Silakan <a href="{{ route('so_by_selected.index') }}">pilih SO Event</a> terlebih dahulu.
    </div>
    @endif

    {{-- Modal untuk Entri Stok Fisik Individual --}}
    <div class="modal fade" id="stockEntryModal" tabindex="-1" aria-labelledby="stockEntryModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="stockEntryModalLabel">Entri Stok Fisik</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="modal_temp_stock_entry_id">
                    <div class="mb-3">
                        <label class="form-label">Nama Produk:</label>
                        <p id="modal_product_name" class="fw-bold"></p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kode Produk:</label>
                        <p id="modal_product_code"></p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Barcode:</label>
                        <p id="modal_product_barcode"></p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Stok Sistem (saat SO disiapkan):</label>
                        <p id="modal_system_stock" class="fw-bold"></p>
                    </div>
                    <div class="mb-3">
                        <label for="modal_physical_stock" class="form-label">Stok Fisik (Input):</label>
                        <input type="number" class="form-control" id="modal_physical_stock" min="0" required>
                        <div class="invalid-feedback" id="modal_error_message"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-warning" id="reset_modal_stock_button">Reset Input</button>
                    <button type="button" class="btn btn-primary" id="save_modal_stock_button">Simpan Stok</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    #entryStockTable tbody tr:hover {
        cursor: pointer;
        background-color: #f5f5f5;
        /* Warna latar saat hover */
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('barcode_search_input');
        const searchButton = document.getElementById('search_product_button');
        const stockEntryModal = new bootstrap.Modal(document.getElementById('stockEntryModal'));
        const modalTempStockEntryId = document.getElementById('modal_temp_stock_entry_id');
        const modalProductName = document.getElementById('modal_product_name');
        const modalProductCode = document.getElementById('modal_product_code');
        const modalProductBarcode = document.getElementById('modal_product_barcode');
        const modalSystemStock = document.getElementById('modal_system_stock');
        const modalPhysicalStockInput = document.getElementById('modal_physical_stock');
        const saveModalStockButton = document.getElementById('save_modal_stock_button');
        const resetModalStockButton = document.getElementById('reset_modal_stock_button');
        const modalCurrentPhysicalStockHidden = document.createElement('input'); // Input tersembunyi untuk menyimpan stok fisik saat ini dari tabel
        modalCurrentPhysicalStockHidden.type = 'hidden';
        modalCurrentPhysicalStockHidden.id = 'modal_current_physical_stock_hidden';
        document.body.appendChild(modalCurrentPhysicalStockHidden); // Tambahkan ke body agar bisa diakses
        const modalErrorMessage = document.getElementById('modal_error_message');

        const soEventId = '{{ $currentEvent->id ?? null }}';
        const nomorNota = '{{ $displayNomorNota ?? null }}';

        function findAndShowProduct(barcodeOrCode) {
            if (!barcodeOrCode.trim()) {
                Swal.fire('Info', 'Silakan masukkan barcode atau kode produk.', 'info');
                return;
            }

            fetch(`{{ route('stock_check.find_product_for_entry') }}?barcode_or_code=${encodeURIComponent(barcodeOrCode)}&so_event_id=${soEventId}&nomor_nota=${nomorNota}`, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    }
                })
                .then(response => {
                    // Coba parse respons sebagai JSON. Baik respons sukses (200) maupun
                    // respons 'tidak ditemukan' (404) dari findProductForEntry diharapkan mengembalikan JSON.
                    // Simpan status 'ok' dari respons untuk digunakan di blok .then berikutnya.
                    return response.json().then(data => ({
                        ok: response.ok,
                        status: response.status,
                        data
                    }));
                })
                .then(({
                    ok,
                    status,
                    data
                }) => { // Destrukturisasi objek dari .then sebelumnya
                    if (ok && data.success && data.product) { // Kasus sukses: HTTP 200 dan success: true
                        const product = data.product;
                        modalTempStockEntryId.value = product.temp_stock_entry_id;
                        modalProductName.textContent = product.name;
                        modalProductCode.textContent = product.product_code;
                        modalProductBarcode.textContent = product.barcode;
                        modalSystemStock.textContent = product.system_stock;

                        // Simpan nilai physical_stock yang ada (dari database/temp_entry) ke input tersembunyi
                        // Ini adalah nilai yang sudah tersimpan SEBELUM pengguna input di modal
                        modalCurrentPhysicalStockHidden.value = product.physical_stock !== null ? product.physical_stock : '0';

                        // Kosongkan input modal untuk entri baru, atau bisa juga diisi dengan 0
                        modalPhysicalStockInput.value = '';

                        modalPhysicalStockInput.classList.remove('is-invalid');
                        modalErrorMessage.textContent = '';
                        stockEntryModal.show();
                        modalPhysicalStockInput.placeholder = `Stok tersimpan: ${parseInt(modalCurrentPhysicalStockHidden.value)}. Input tambahan...`;
                        setTimeout(() => modalPhysicalStockInput.focus(), 500); // Fokus setelah modal tampil
                    } else if (!ok && data && typeof data.message !== 'undefined') { // Kasus tidak ok (misal 404), tapi ada JSON message dari API
                        Swal.fire('Tidak Ditemukan', data.message, 'error'); // Gunakan pesan dari API
                        if (searchInput) searchInput.focus();
                    } else {
                        // Kasus lain: HTTP 200 tapi success:false, atau struktur data tidak terduga
                        Swal.fire('Info', data.message || 'Produk tidak ditemukan atau terjadi kesalahan respons.', 'info');
                        if (searchInput) searchInput.focus();
                    }
                })
                .catch(error => {
                    // Blok ini akan menangkap error jaringan atau jika response.json() gagal (misal, server mengembalikan HTML untuk error 500)
                    console.error('Error fetching product:', error);
                    let userMessage = 'Terjadi kesalahan saat mencari produk.';
                    if (error instanceof SyntaxError) { // Jika parsing JSON gagal karena respons bukan JSON
                        userMessage = 'Respons tidak valid dari server (bukan JSON). Periksa konsol browser untuk detail.';
                    } else if (error.message) { // Untuk error jaringan lainnya
                        userMessage = `Gagal menghubungi server: ${error.message}. Periksa koneksi Anda.`;
                    }
                    Swal.fire('Error', userMessage, 'error');
                    if (searchInput) searchInput.focus();
                });
        }

        if (resetModalStockButton) {
            resetModalStockButton.addEventListener('click', function() {
                const tempEntryId = modalTempStockEntryId.value;
                if (!tempEntryId) {
                    // Hanya reset inputan di modal jika tidak ada item spesifik yang dipilih (seharusnya tidak terjadi jika modal terbuka)
                    modalPhysicalStockInput.value = '';
                    modalPhysicalStockInput.classList.remove('is-invalid');
                    modalErrorMessage.textContent = '';
                    modalPhysicalStockInput.focus();
                    return;
                }

                Swal.fire({
                    title: 'Anda yakin?',
                    text: "Stok fisik yang tersimpan untuk item ini akan direset (dihapus). Anda tidak dapat mengembalikan tindakan ini!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Ya, reset!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch(`{{ route('stock_check.reset_single_stock') }}`, {
                                method: 'POST',
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                },
                                body: JSON.stringify({
                                    temp_stock_entry_id: tempEntryId,
                                    so_event_id: soEventId // Kirim juga so_event_id untuk validasi
                                })
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    Swal.fire('Direset!', 'Stok fisik item telah direset.', 'success');
                                    modalCurrentPhysicalStockHidden.value = '0'; // Karena null akan jadi '0'
                                    modalPhysicalStockInput.value = ''; // Input tambahan tetap dikosongkan
                                    modalPhysicalStockInput.placeholder = `Stok tersimpan: 0. Input tambahan...`; // Gunakan placeholder yang konsisten
                                    modalPhysicalStockInput.classList.remove('is-invalid');
                                    modalErrorMessage.textContent = '';
                                    modalPhysicalStockInput.focus();

                                    const mainTableInput = document.querySelector(`input[name="products[${tempEntryId}][recorded_stock]"]`);
                                    if (mainTableInput) {
                                        mainTableInput.value = '0'; // Set ke '0' di tabel utama setelah reset
                                    }
                                } else {
                                    Swal.fire('Gagal', data.message || 'Gagal mereset stok.', 'error');
                                }
                            })
                            .catch(error => {
                                console.error('Error resetting stock:', error);
                                Swal.fire('Error', 'Terjadi kesalahan koneksi saat mereset stok.', 'error');
                            });
                    }
                });
            });
        }

        if (searchButton) {
            searchButton.addEventListener('click', function() {
                findAndShowProduct(searchInput.value);
            });
        }

        if (searchInput) {
            searchInput.addEventListener('keypress', function(event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    findAndShowProduct(searchInput.value);
                }
            });
        }

        saveModalStockButton.addEventListener('click', function() {
            const tempEntryId = modalTempStockEntryId.value;
            const additionalStockInput = modalPhysicalStockInput.value;
            const currentStoredStock = parseInt(modalCurrentPhysicalStockHidden.value) || 0;
            let newTotalStock;

            if (additionalStockInput === '') { // Jika pengguna tidak input apa-apa, anggap 0 tambahan
                newTotalStock = currentStoredStock; // Tidak ada perubahan
            } else {
                const additionalStock = parseInt(additionalStockInput);
                if (isNaN(additionalStock) || additionalStock < 0) {
                    modalPhysicalStockInput.classList.add('is-invalid');
                    modalErrorMessage.textContent = 'Input stok tambahan harus berupa angka dan tidak boleh kurang dari 0.';
                    return;
                }
                newTotalStock = currentStoredStock + additionalStock;
            }

            if (newTotalStock < 0) { // Seharusnya tidak terjadi jika input tambahan >= 0
                modalPhysicalStockInput.classList.add('is-invalid');
                modalErrorMessage.textContent = 'Total stok tidak boleh kurang dari 0.';
                return;
            }
            // Pastikan untuk menghapus kelas invalid dan pesan error jika validasi lolos
            modalPhysicalStockInput.classList.remove('is-invalid');
            modalErrorMessage.textContent = '';

            fetch(`{{ route('stock_check.update_single_stock') }}`, { // <-- fetch() was missing here
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        temp_stock_entry_id: tempEntryId,
                        recorded_stock: newTotalStock, // Kirim total stok yang baru
                        so_event_id: soEventId // Kirim juga so_event_id untuk validasi di backend
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        stockEntryModal.hide();
                        Swal.fire('Sukses', 'Stok fisik berhasil diperbarui.', 'success');
                        searchInput.value = ''; // Kosongkan input search
                        searchInput.focus(); // Fokus kembali ke input search

                        // Update nilai di tabel utama jika item ada di halaman saat ini
                        // dan juga perbarui nilai di input yang mungkin ada di tabel utama.
                        const mainTableInput = document.querySelector(`input[name="products[${tempEntryId}][recorded_stock]"]`);
                        if (mainTableInput) {
                            mainTableInput.value = newTotalStock;
                        }
                        // Jika ingin reload halaman untuk melihat perubahan (misal jika ada paginasi dan item tidak di halaman ini)
                        // window.location.reload();
                    } else {
                        modalPhysicalStockInput.classList.add('is-invalid');
                        modalErrorMessage.textContent = data.message || 'Gagal menyimpan stok.';
                        Swal.fire('Error', data.message || 'Gagal menyimpan stok.', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error saving stock:', error);
                    modalPhysicalStockInput.classList.add('is-invalid');
                    modalErrorMessage.textContent = 'Terjadi kesalahan koneksi.';
                    Swal.fire('Error', 'Terjadi kesalahan koneksi saat menyimpan stok.', 'error');
                });
        });

        // Clear search input when modal is hidden
        document.getElementById('stockEntryModal').addEventListener('hidden.bs.modal', function() {
            if (searchInput) {
                searchInput.value = '';
                // searchInput.focus(); // Opsional: langsung fokus kembali
            }
        });

        // Tambahkan event listener untuk klik pada baris tabel
        const entryTableRows = document.querySelectorAll('#entryStockTable tbody tr');
        entryTableRows.forEach(row => {
            row.addEventListener('click', function(event) {
                // Hindari trigger jika yang diklik adalah input di dalam sel
                if (event.target.tagName === 'INPUT') {
                    return;
                }

                const barcodeCell = this.querySelector('td[data-product-barcode]');
                const productCodeCell = this.querySelector('td[data-product-code]');

                let identifier = barcodeCell ? barcodeCell.dataset.productBarcode : null;
                if (!identifier || identifier === 'N/A' || identifier.trim() === '') {
                    identifier = productCodeCell ? productCodeCell.dataset.productCode : null;
                }

                if (identifier && identifier !== 'N/A' && identifier.trim() !== '') {
                    findAndShowProduct(identifier);
                }
            });
        });
    });
</script>
@endpush
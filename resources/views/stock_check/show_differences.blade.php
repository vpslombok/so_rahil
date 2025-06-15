@extends('layouts.app')

@section('title', 'Selisih Stok Fisik untuk SO: ' . $stock_opname_event->name . ($nomorNota ? ' (Nota: ' . $nomorNota . ')' : ''))

@section('content')
<div class="container-fluid" id="app">
    <h1 class="h3 mb-2 text-gray-800">
        Selisih Stok Fisik: <span class="text-primary">{{ $stock_opname_event->name }}</span>
        @if($nomorNota)
        <small class="text-muted">(No. Nota: {{ $nomorNota }})</small>
        @endif
    </h1>
    <p class="mb-4">Halaman ini menampilkan hasil perbandingan antara stok sistem dan stok fisik yang telah Anda entri.</p>

    @include('layouts.flash-messages')

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Detail Selisih Stok</h6>
            <div>
                {{-- Tambahkan tombol aksi di sini jika perlu, misalnya "Finalisasi SO" atau "Cetak Laporan" --}}
                {{-- <a href="#" class="btn btn-primary btn-sm"><i class="bi bi-check-circle"></i> Finalisasi SO</a> --}}
            </div>
        </div>
        <div class="card-body">
            @if($differences->isEmpty())
            <p class="text-center">Tidak ada item dengan selisih stok untuk ditampilkan.</p>
            @else
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="differencesTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th style="width: 5%;">No</th>
                            <th style="width: 15%;">Kode Produk</th>
                            <th style="width: 20%;">Barcode</th>
                            <th>Nama Produk</th>
                            <th style="width: 10%;" class="text-center">Stok Sistem</th>
                            <th style="width: 10%;" class="text-center">Stok Fisik</th>
                            <th style="width: 10%;" class="text-center">Selisih</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($differences as $index => $item)
                        {{-- Jika filter sudah di controller, kondisi if ini tidak lagi esensial, tapi tidak berbahaya --}}
                        <tr class="clickable-row {{ $item->difference != 0 ? ($item->difference < 0 ? 'table-danger' : 'table-warning') : '' }}"
                            data-bs-toggle="modal"
                            data-bs-target="#editDifferenceModal"
                            data-product="{{ json_encode([
                                'product_id' => $item->product->id,
                                'product_name' => $item->product->name,
                                'system_stock' => $item->system_stock,
                                'physical_stock' => $item->physical_stock,
                            ]) }}">
                            <td>{{ $differences->firstItem() + $index }}</td>
                            <td>{{ $item->product->product_code ?? 'N/A' }}</td>
                            <td>{{ $item->product->barcode ?? 'N/A' }}</td>
                            <td>{{ $item->product->name ?? 'Produk Tidak Ditemukan' }}</td>
                            <td class="text-center">{{ $item->system_stock }}</td>
                            <td class="text-center">{{ $item->physical_stock ?? '-' }}</td>
                            <td class="text-center font-weight-bold">
                                @if(is_null($item->physical_stock) || is_null($item->system_stock))
                                -
                                @else
                                {{ $item->difference }}
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($differences->hasPages())
            <div class="d-flex justify-content-center mt-3">
                {{ $differences->appends(request()->except('page'))->links('vendor.pagination.custom') }}
            </div>
            @endif
            @endif
        </div>
        <div class="card-footer text-center">
            <a href="{{ route('stock_check.index', ['stock_opname_event' => $stock_opname_event->id]) }}" class="btn btn-info">
                <i class="bi bi-pencil-square"></i> Kembali ke Entri Stok
            </a>
            {{-- Tombol Finalisasi SO --}}
            {{-- Pastikan route 'stock_check.finalize_event' sudah ada dan mengarah ke controller yang benar --}}
            <form action="{{ route('stock_check.finalize_event', ['stock_opname_event' => $stock_opname_event->id, 'nomor_nota' => $nomorNota]) }}" method="POST" class="d-inline" onsubmit="return confirm('Anda yakin ingin memfinalisasi SO Event ini (Nota: {{ $nomorNota }})? Data tidak dapat diubah setelah finalisasi.');">
                @csrf
                <button type="submit" class="btn btn-success ms-2">
                    <i class="bi bi-check-all"></i> Finalisasi Stock Opname
                </button>
            </form>
            <a href="{{ route('so_by_selected.index') }}" class="btn btn-secondary ms-2">
                <i class="bi bi-list-check"></i> Pilih SO Event Lain
            </a>
        </div>
    </div>

    <!-- Modal Edit Selisih Stok -->
    <div class="modal fade" id="editDifferenceModal" tabindex="-1" aria-labelledby="editDifferenceModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title" id="editDifferenceModalLabel">Edit Stok Fisik</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Nama Produk: <span id="editProductName" class="fw-bold"></span></p>
                    <p>Stok Sistem: <span id="editSystemStock" class="fw-bold"></span></p>
                    <div class="mb-3">
                        <label for="editPhysicalStock" class="form-label">Stok Fisik</label>
                        <input type="number" class="form-control" id="editPhysicalStock" min="0">
                    </div>
                    <p class="text-muted">
                        Dengan menyimpan perubahan ini, Anda mengonfirmasi bahwa stok fisik
                        untuk produk ini adalah nilai yang Anda masukkan di atas.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-warning" id="savePhysicalStockChanges">
                        <i class="bi bi-save me-1"></i> Simpan Perubahan
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const editModal = document.getElementById('editDifferenceModal');
        const editProductName = document.getElementById('editProductName');
        const editSystemStock = document.getElementById('editSystemStock');
        const editPhysicalStock = document.getElementById('editPhysicalStock');
        const saveButton = document.getElementById('savePhysicalStockChanges');
        let currentProductId; // Simpan ID produk yang sedang diedit

        editModal.addEventListener('show.bs.modal', function(event) {
            const row = event.relatedTarget;
            const productData = JSON.parse(row.dataset.product);
            currentProductId = productData.product_id; // Ambil product ID
            editProductName.textContent = productData.product_name;
            editSystemStock.textContent = productData.system_stock;
            editPhysicalStock.value = productData.physical_stock;
        });

        saveButton.addEventListener('click', function() {
            const newPhysicalStock = editPhysicalStock.value;
            const soEventIdConst = '{{ $stock_opname_event->id }}';
            const nomorNotaConst = '{{ $nomorNota }}';

            if (newPhysicalStock === '' || isNaN(newPhysicalStock) || parseInt(newPhysicalStock) < 0) {
                Swal.fire('Error', 'Stok fisik harus berupa angka dan tidak boleh kurang dari 0.', 'error');
                editPhysicalStock.focus();
                return;
            }

            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            fetch(`{{ route('stock_check.update_physical_stock_from_differences', ['nomor_nota' => $nomorNota]) }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        product_id: currentProductId,
                        physical_stock: parseInt(newPhysicalStock),
                        nomor_nota: nomorNotaConst
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const modalInstance = bootstrap.Modal.getInstance(editModal);
                        modalInstance.hide();
                        Swal.fire('Sukses!', data.message || 'Stok fisik berhasil diperbarui.', 'success')
                            .then(() => {
                                window.location.reload(); // Reload halaman untuk melihat perubahan
                            });
                    } else {
                        Swal.fire('Gagal!', data.message || 'Gagal memperbarui stok.', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire('Error!', 'Terjadi kesalahan koneksi. Silakan coba lagi.', 'error');
                });
        });
    });
</script>
@endpush
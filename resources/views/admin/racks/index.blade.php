@extends('layouts.app')

@section('title', 'Manajemen Rak')

@section('content')
<div class="container-fluid">
    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0 text-gray-800">Manajemen Rak</h1>
        <a href="{{ route('admin.racks.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle me-1"></i> Tambah Rak Baru
        </a>
    </div>

    {{-- Flash Messages --}}
    @include('layouts.flash-messages')

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Daftar Rak</h6>
        </div>
        <div class="card-body">
            @if($racks->isEmpty())
                <div class="alert alert-info">
                    Belum ada data rak. Silakan <a href="{{ route('admin.racks.create') }}">tambahkan rak baru</a>.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="racksTable" width="100%" cellspacing="0">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Nama Rak</th>
                                <th>Lokasi</th>
                                <th>Deskripsi</th>
                                <th>Tgl Dibuat</th>
                                <th>Produk di Rak</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($racks as $index => $rack)
                            <tr>
                                <td>{{ $racks->firstItem() + $index }}</td>
                                <td>{{ $rack->name }}</td>
                                <td>{{ $rack->location ?? '-' }}</td>
                                <td class="text-truncate" style="max-width: 200px;" title="{{ $rack->description }}">{{ Str::limit($rack->description, 50) ?? '-' }}</td>
                                <td>{{ $rack->created_at->format('d/m/Y H:i') }}</td>
                                <td>
                                    @if($rack->products->count() > 0)
                                        <button class="btn btn-sm btn-outline-info" type="button" data-bs-toggle="collapse" data-bs-target="#products-rack-{{ $rack->id }}" aria-expanded="false" aria-controls="products-rack-{{ $rack->id }}">
                                            <i class="bi bi-list-ul me-1"></i> Lihat ({{ $rack->products->count() }})
                                        </button>
                                    @else
                                        <span class="badge bg-secondary">Kosong</span>
                                    @endif
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-primary me-1 edit-rack-btn" title="Edit"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editRackModal"
                                            data-id="{{ $rack->id }}"
                                            data-name="{{ $rack->name }}"
                                            data-location="{{ $rack->location ?? '' }}"
                                            data-description="{{ $rack->description ?? '' }}"
                                            data-action="{{ route('admin.racks.update', $rack->id) }}">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger" title="Hapus"
                                            onclick="confirmDelete('{{ $rack->id }}', '{{ $rack->name }}')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    <form id="delete-form-{{ $rack->id }}" action="{{ route('admin.racks.destroy', $rack->id) }}" method="POST" style="display: none;">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                </td>
                            </tr>
                            @if($rack->products->count() > 0)
                            <tr class="collapse" id="products-rack-{{ $rack->id }}">
                                <td colspan="7"> {{-- Sesuaikan colspan dengan jumlah total kolom di thead --}}
                                    <div class="p-3 bg-light border rounded">
                                        <h6 class="mb-2"><i class="bi bi-box-seam"></i> Produk di Rak: <strong>{{ $rack->name }}</strong></h6>
                                        <ul class="list-group list-group-flush">
                                            @foreach($rack->products as $product)
                                                <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                                                    <div>
                                                        <a href="{{ route('dashboard', ['search_product' => $product->barcode, 'rack_id_filter' => $rack->id]) }}" target="_blank" class="text-decoration-none">
                                                            {{ $product->name }}
                                                        </a>
                                                        <small class="text-muted d-block">Kode: {{ $product->product_code }} | Barcode: {{ $product->barcode }}</small>
                                                    </div>
                                                    <a href="{{ route('dashboard', ['search_product' => $product->barcode, 'rack_id_filter' => $rack->id]) }}" class="btn btn-sm btn-outline-secondary" target="_blank" title="Lihat Detail Produk di Dashboard">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                            @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
                {{-- Pagination --}}
                @if($racks->hasPages())
                <div class="d-flex justify-content-center mt-3">
                    {{ $racks->links('vendor.pagination.custom') }}
                </div>
                @endif
            @endif
        </div>
    </div>
</div>

<!-- Edit Rack Modal -->
<div class="modal fade" id="editRackModal" tabindex="-1" aria-labelledby="editRackModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editRackModalLabel">Edit Rak</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editRackForm" method="POST" action=""> {{-- Action will be set by JS --}}
                @csrf
                @method('PUT')
                <div class="modal-body">
                    {{-- Form fields will mirror _form.blade.php structure --}}
                    {{-- Note: `old()` and `@error` will work here if validation fails and page reloads --}}
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Nama Rak <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name', 'updateRack') is-invalid @enderror" id="edit_name" name="name" value="{{ old('name') }}" required>
                        @error('name', 'updateRack') {{-- Specify error bag if needed, or ensure controller redirects with specific errors --}}
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="edit_location" class="form-label">Lokasi</label>
                        <input type="text" class="form-control @error('location', 'updateRack') is-invalid @enderror" id="edit_location" name="location" value="{{ old('location') }}">
                        @error('location', 'updateRack')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Deskripsi</label>
                        <textarea class="form-control @error('description', 'updateRack') is-invalid @enderror" id="edit_description" name="description" rows="3">{{ old('description') }}</textarea>
                        @error('description', 'updateRack')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Update Rak</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function confirmDelete(id, name) {
    Swal.fire({
        title: 'Anda yakin?',
        text: `Rak "${name}" akan dihapus permanen!`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('delete-form-' + id).submit();
        }
    })
}
</script>
@endsection

@push('styles')
@endpush

@push('scripts')
{{-- Pastikan SweetAlert2 sudah di-include di layout utama atau di sini jika belum --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    var editRackModalEl = document.getElementById('editRackModal');
    if (editRackModalEl) {
        var editRackModal = new bootstrap.Modal(editRackModalEl);

        editRackModalEl.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget; // Button that triggered the modal

            var rackName = button.getAttribute('data-name');
            var rackLocation = button.getAttribute('data-location');
            var rackDescription = button.getAttribute('data-description');
            var actionUrl = button.getAttribute('data-action');

            var modalTitle = editRackModalEl.querySelector('.modal-title');
            var form = editRackModalEl.querySelector('#editRackForm');
            var inputName = editRackModalEl.querySelector('#edit_name');
            var inputLocation = editRackModalEl.querySelector('#edit_location');
            var inputDescription = editRackModalEl.querySelector('#edit_description');

            modalTitle.textContent = 'Edit Rak: ' + rackName;
            form.action = actionUrl;

            // Populate form fields with current data.
            // If `old()` has values (due to a previous failed validation attempt),
            // Blade would have populated them. This JS will then overwrite with actual current data.
            // This means on re-opening modal after error, user sees original data + error messages.
            inputName.value = rackName;
            inputLocation.value = rackLocation;
            inputDescription.value = rackDescription;
        });

        // If there were validation errors for the update form (e.g., from RackController)
        // and you want to automatically re-open the modal.
        // This requires the controller to redirect back with a specific session key.
        @if ($errors->hasBag('updateRack') || ($errors->any() && old('name'))) // A way to check if errors are for this form
            // Check if there's a specific rack ID that failed, if passed via session
            // For simplicity, if any update error, and user manually re-opens, old() values are used by Blade.
            // The JS above will then overwrite with current data.
        @endif
    }
});
</script>
@endpush
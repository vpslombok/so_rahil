<!-- Select Shelf Modal -->
<div class="modal fade" id="selectShelfModal" tabindex="-1" aria-labelledby="selectShelfModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="selectShelfModalLabel"><i class="bi bi-grid-3x3-gap me-2"></i>Pilih Rak</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <input type="text" id="shelfSearchInput" class="form-control" placeholder="Cari rak berdasarkan Nama atau Kode...">
                </div>
                <div class="table-responsive">
                    <table class="table table-hover" id="shelfSelectionTable">
                        <thead class="table-light">
                            <tr>
                                <th>Nama Rak</th>
                                <th>Deskripsi</th>
                                <th>Lokasi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(isset($availableShelvesForModal) && $availableShelvesForModal->count() > 0)
                            @foreach($availableShelvesForModal as $shelf)
                            <tr class="shelf-row" data-shelf-identifier="{{ $shelf->code ?: $shelf->name }}" style="cursor: pointer;">
                                <td>{{ $shelf->name }}</td>
                                <td>{{ $shelf->description ?: '-' }}</td>
                                <td>{{ $shelf->location ?: '-' }}</td>
                            </tr>
                            @endforeach
                            @else
                            <tr>
                                <td colspan="3" class="text-center text-muted">Tidak ada rak yang tersedia.</td>
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
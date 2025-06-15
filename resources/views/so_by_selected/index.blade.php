@extends('layouts.app')

@section('title', 'Stok Opname Produk Pilihan')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Pilih SO untuk Entry Stok</h1>

    @include('layouts.flash-messages')

    {{-- Form Pemilihan Event --}}
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Pilih SO</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('so_by_selected.index') }}"> {{-- Diubah ke GET agar parameter terlihat di URL dan mudah di-bookmark/share --}}
                @csrf {{-- Tambahkan CSRF token untuk POST request --}}
                <div class="row gx-2 gy-2 align-items-center">
                    <div class="col-md-auto">
                        <div class="d-flex align-items-center">
                            <label for="event_id" class="form-label me-2 mb-0 text-nowrap">Pilih SO:</label>
                            <select name="event_id" id="event_id" class="form-select form-select-sm" required style="min-width: 200px; width: auto;">
                                <option value="">-- Pilih SO --</option>
                                @foreach($activeSoEvents as $event)
                                <option value="{{ $event->id }}" {{ (isset($selectedEventId) && $selectedEventId == $event->id) ? 'selected' : '' }}>
                                    {{ $event->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-auto">
                        <button type="submit" class="btn btn-info btn-sm"><i class="bi bi-check-circle"></i> Tampilkan</button>
                    </div>
                </div>
            </form>

            {{-- Tombol "Simpan & Lanjut Entry" dipisahkan ke form sendiri di luar form utama --}}
            {{-- Tombol muncul jika ada produk yang ditampilkan (baik dari filter SO atau Rak) --}}
            @if(isset($selectedEventId) && isset($productsToDisplay) && $productsToDisplay->count() > 0)
            <div class="mt-3"> {{-- Tambahkan margin atas untuk pemisah visual --}}
                <form method="POST" action="{{ route('so_by_selected.prepare_for_entry') }}" style="display: inline;" id="prepareEntryForm">
                    @csrf
                    <input type="hidden" name="event_id" value="{{ $selectedEventId }}">
                    {{-- filter_mode tidak lagi relevan karena hanya event_id yang jadi pemicu utama dari halaman ini --}}
                    {{-- <input type="hidden" name="filter_mode" value="event_primary"> --}}
                    {{-- rack_id tidak lagi dikirim dari sini --}}
                    {{-- <input type="hidden" name="rack_id" value="{{ $selectedRackId ?? '' }}"> --}}
                    <button type="submit" class="btn btn-success btn-sm">
                        <i class="bi bi-box-arrow-in-right"></i> Simpan & Lanjut Entry
                    </button>
                </form>
            </div>
            {{-- Pesan jika ada filter aktif tapi tidak ada produk --}}
            @elseif(isset($selectedEventId) && isset($productsToDisplay) && $productsToDisplay->isEmpty() && !session('info_message'))
            <div class="mt-3">
                <p class="text-warning"><i class="bi bi-exclamation-triangle"></i> Tidak ada produk yang cocok dengan filter yang Anda pilih. Tidak dapat melanjutkan entri.</p>
            </div>
            @endif
        </div>
    </div>

    {{-- Daftar Produk jika ada filter aktif (Event atau Rak) dan ada produk untuk ditampilkan --}}
    @if(isset($selectedEventId) && isset($productsToDisplay) && $productsToDisplay->count() > 0)
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    @if(isset($currentEvent)) Daftar Produk untuk SO: {{ $currentEvent->name }} @else
                        Daftar Produk
                    @endif
                </h6>
            </div>
            <div class="card-body">
                @if(session('info_message'))
                <div class="alert alert-info">{{ session('info_message') }}</div>
                @endif
                <div class="table-responsive">
                    <table class="table table-bordered" id="soProductTable">
                        <thead>
                            <tr>
                                <th style="width: 5%;">No</th>
                                <th style="width: 15%;">Kode Produk</th>
                                <th style="width: 20%;">Barcode</th>
                                <th>Nama Produk</th>
                                <th style="width: 15%;">Rak</th> {{-- Tambah kolom Rak --}}
                                {{-- Kolom Stok Fisik dihilangkan --}}
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($productsToDisplay as $index => $item)
                                @php
                                    // $item->product akan ada jika dari SoSelectedProduct atau jika dibungkus oleh controller
                                    // Jika $productsToDisplay adalah koleksi Product langsung (saat filter by rack only), maka $item adalah Product itu sendiri.
                                    // Controller sudah dimodifikasi untuk membungkus Product agar selalu ada $item->product
                                    $product = $item->product;
                                @endphp
                                <tr>
                                    <td>{{ $productsToDisplay->firstItem() + $index }}</td>
                                    <td>
                                        {{ $product->product_code ?? 'N/A' }}
                                    </td>
                                    <td>
                                        {{ $product->barcode ?? 'N/A' }}
                                    </td>
                                    <td>{{ $product->name ?? 'Produk Tidak Ditemukan' }}</td>
                                    <td>{{ $product->rack->name ?? '-' }}</td> {{-- Tampilkan nama rak --}}
                                    {{-- Data Stok Fisik dihilangkan --}}
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($productsToDisplay->hasPages())
                    <div class="d-flex justify-content-center mt-3">
                        {{ $productsToDisplay->appends(['event_id' => $selectedEventId])->links('vendor.pagination.custom') }}
                    </div>
                @endif
            </div>
        </div>
    {{-- Pesan jika ada filter aktif tapi tidak ada hasil --}}
    @elseif(isset($selectedEventId) && isset($productsToDisplay) && $productsToDisplay->isEmpty() && !session('info_message'))
        <p class="text-center mt-3">Tidak ada produk yang cocok dengan filter yang Anda pilih.</p>
    @elseif(isset($activeSoEvents) && $activeSoEvents->isNotEmpty() && !isset($selectedEventId) && !isset($selectedRackId))
        <p class="text-center mt-3">Silakan pilih SO Event atau Rak di atas untuk menampilkan produk.</p>
    @endif
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('event_id')?.addEventListener('change', function() {
            this.form.submit();
        });
        // Remove rack_id listener as the element is removed
        // const prepareEntryForm related logic is also removed as the conditional rendering for it has changed.
        // The form #prepareEntryForm will only show if $selectedEventId is present, so event_id is guaranteed.
    });
</script>
@endpush
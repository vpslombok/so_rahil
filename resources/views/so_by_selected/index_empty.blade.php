@extends('layouts.app')

@section('title', 'Entry Stok Fisik - Tidak Ada SO Aktif')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Entry Stok Fisik</h1>

    @include('layouts.flash-messages')
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Tidak Ada SO untuk Di-entry</h6>
        </div>
        <div class="card-body">
            <p class="text-center lead">Saat ini tidak ada SO yang aktif untuk dilakukan Entry Stok Fisik.</p>
            <p class="text-center">Silakan hubungi Administrator untuk mengaktifkan atau membuat SO baru.</p>
            <div class="text-center mt-4">
                <a href="{{ route('dashboard') }}" class="btn btn-info"><i class="bi bi-arrow-left-circle"></i> Kembali ke Dashboard</a>
            </div>
        </div>
    </div>
</div>
@endsection

@extends('layouts.app')

@section('title', 'Tambah Rak Baru')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Tambah Rak Baru</h1>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <form action="{{ route('admin.racks.store') }}" method="POST">
                @include('admin.racks._form')
                <button type="submit" class="btn btn-primary">Simpan Rak</button>
                <a href="{{ route('admin.racks.index') }}" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>
@endsection
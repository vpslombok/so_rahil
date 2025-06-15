@extends('layouts.app')

@section('title', 'Edit Rak')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Edit Rak: {{ $rack->name }}</h1>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <form action="{{ route('admin.racks.update', $rack->id) }}" method="POST">
                @method('PUT')
                @include('admin.racks._form', ['rack' => $rack])
                <button type="submit" class="btn btn-primary">Update Rak</button>
                <a href="{{ route('admin.racks.index') }}" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>
@endsection
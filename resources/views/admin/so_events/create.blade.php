@extends('layouts.app')

@section('title', 'Buat SO Item Rahil Baru')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Buat SO Item Rahil Baru</h1>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Detail SO Item Rahil</h6>
        </div>
        <div class="card-body">
                <form action="{{ route('admin.so-events.store') }}" method="POST">
                @csrf
                @include('admin.so_events._form')
            </form>
        </div>
    </div>
</div>
@endsection

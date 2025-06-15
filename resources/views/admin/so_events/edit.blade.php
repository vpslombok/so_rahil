@extends('layouts.app')

@section('title', 'Edit SO Item Rahil: ' . $so_event->name)

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Edit SO Item Rahil: <span class="text-primary">{{ $so_event->name }}</span></h1>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Detail SO Item Rahil</h6>
        </div>
        <div class="card-body">
                <form action="{{ route('admin.so-events.update', $so_event->id) }}" method="POST">
                @csrf
                @method('PUT')
                @include('admin.so_events._form', ['so_event' => $so_event])
            </form>
        </div>
    </div>
</div>
@endsection

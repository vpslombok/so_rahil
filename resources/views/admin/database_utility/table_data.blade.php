@extends('layouts.app')

@section('title', 'Data Tabel ' . $tableName)

@section('content')
<div class="container-fluid">
    <div class="text-center pt-2 pb-2 mb-3 border-bottom">
        <h1 class="h2 d-inline-block mb-0 me-2 align-middle">Data Tabel: <code>{{ $tableName }}</code></h1>
        <a href="{{ route('admin.database.utility') }}" class="btn btn-sm btn-outline-secondary align-middle">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Struktur Tabel</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-bordered">
                    <thead>
                        <tr>
                            <th>Kolom</th>
                            <th>Tipe</th>
                            <th>Nullable</th>
                            <th>Key</th>
                            <th>Default</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tableStructure as $column)
                        <tr>
                            <td><code>{{ $column['name'] }}</code></td>
                            <td>{{ $column['type'] }}</td>
                            <td>{{ $column['nullable'] ? 'Ya' : 'Tidak' }}</td>
                            <td>{{ $column['key'] ?: '-' }}</td>
                            <td>{{ $column['default'] ?: 'NULL' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Data Tabel</h5>
            <span class="badge bg-primary">
                Total: {{ $data->total() }} data
            </span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover table-bordered">
                    <thead class="table-dark">
                        <tr>
                            @foreach($columns as $column)
                                <th>{{ $column }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($data as $row)
                            <tr>
                                @foreach($columns as $column)
                                    <td>
                                        @if(is_array($row->{$column} ?? null) || is_object($row->{$column} ?? null))
                                            {{ json_encode($row->{$column}) }}
                                        @else
                                            {{ $row->{$column} ?? 'NULL' }}
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($columns) }}" class="text-center">Tidak ada data</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $data->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
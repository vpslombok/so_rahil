@extends('layouts.app')

@section('title', 'Log Penggunaan REST API')

@section('content')
<div class="container py-4">
    <div class="row mb-4">
        <div class="col">
            <h1 class="h3 text-primary mb-0"><i class="bi bi-plug-fill me-2"></i>Log Penggunaan REST API</h1>
            <p class="text-muted">Riwayat akses endpoint REST API oleh user aplikasi.</p>
        </div>
    </div>
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>User</th>
                            <th>Endpoint</th>
                            <th>Method</th>
                            <th>Waktu</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $i => $log)
                        <tr>
                            <td>{{ $i+1 }}</td>
                            <td>{{ $log->user_name ?? '-' }}</td>
                            <td><code>{{ $log->endpoint }}</code></td>
                            <td><span class="badge bg-{{ $log->method === 'GET' ? 'primary' : 'success' }}">{{ $log->method }}</span></td>
                            <td>{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                            <td>{{ $log->ip_address }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">Belum ada log penggunaan REST API.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4 alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                Hanya menampilkan log penggunaan REST API per user. Untuk fitur filter, pencarian, atau detail, silakan kembangkan sesuai kebutuhan.
            </div>
        </div>
    </div>
</div>
@endsection
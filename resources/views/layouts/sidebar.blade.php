@php
$current_route_name = Route::currentRouteName();

// Item Navigasi Utama (untuk semua user yang login)
$main_nav_items = [
['route_name' => 'dashboard', 'active_pattern' => 'dashboard*', 'icon' => 'bi-speedometer2', 'text' => 'Dashboard Produk'],
['route_name' => 'so_by_selected.index', 'active_pattern' => 'so_by_selected.*', 'icon' => 'bi-check-square-fill', 'text' => 'Pilih SO'],
['route_name' => 'stock_check.index', 'active_pattern' => 'stock_check.*', 'icon' => 'bi-clipboard-check-fill', 'text' => 'Entry Stok Fisik'],
['route_name' => 'stock_audit_report.summary', 'active_pattern' => 'stock_audit_report.*', 'icon' => 'bi-file-earmark-text-fill', 'text' => 'Laporan Selisih'],
['route_name' => 'documentation', 'active_pattern' => 'documentation', 'icon' => 'bi-stars', 'text' => 'Fitur Aplikasi'], // Perubahan teks dan ikon (opsional)
];

// Item Navigasi Khusus Admin
$admin_nav_items = [];
if (Auth::check() && Auth::user()->role == 'admin') {
// Perhatikan penggunaan prefix 'admin.' pada route_name sesuai dengan routes/web.php
$admin_nav_items = [
['route_name' => 'admin.users.index', 'active_pattern' => 'admin.users.*', 'icon' => 'bi-people-fill', 'text' => 'Manajemen User'],
['route_name' => 'admin.so-events.index', 'active_pattern' => 'admin.so-events.*', 'icon' => 'bi-calendar2-event-fill', 'text' => 'Manajemen SO'], // Menggunakan tanda hubung
['route_name' => 'admin.so_monitor.index', 'active_pattern' => 'admin.so_monitor.*', 'icon' => 'bi-binoculars-fill', 'text' => 'Monitor SO'],
['route_name' => 'admin.racks.index', 'active_pattern' => 'admin.racks.*', 'icon' => 'bi-bookshelf', 'text' => 'Manajemen Rak'],
['route_name' => 'admin.api_log.index', 'active_pattern' => 'admin.api_log.*', 'icon' => 'bi-journal-text', 'text' => 'Log API'],
['route_name' => 'admin.flutter_app.manager', 'active_pattern' => 'admin.flutter_app.*', 'icon' => 'bi-phone-fill', 'text' => 'Upload Aplikasi Flutter'],
['route_name' => 'admin.database.utility', 'active_pattern' => 'admin.database.utility', 'icon' => 'bi-database-gear', 'text' => 'Manajemen Database'],
['route_name' => 'admin.api_control.index', 'active_pattern' => 'admin.api_control.*', 'icon' => 'bi-plug-fill', 'text' => 'Kontrol REST API'],
];
}
@endphp

{{-- Sidebar untuk layar besar (selalu terlihat di dalam .sidebar-wrapper) --}}
<div class="sidebar-wrapper d-none d-lg-block">
    <nav id="sidebarMenuMain" class="bg-light sidebar border-end shadow-sm" style="min-width: 260px; max-width: 260px; height: 100%;">
        <div class="d-flex flex-column h-100" style="overflow-y: auto;">
            <div class="px-3 mb-3 d-flex align-items-center">
                {{-- Anda bisa menambahkan logo di sini --}}
                {{-- <img src="{{ asset('path/to/your/logo.png') }}" alt="Logo" style="height: 32px;" class="me-2"> --}}
                <a class="navbar-brand fs-5 fw-semibold" href="{{ route('dashboard') }}">
                    <i class="bi bi-box-seam-fill me-1"></i> {{ config('app.name', 'Stok Opname') }}
                </a>
            </div>

            <ul class="nav flex-column px-2 flex-grow-1"> {{-- flex-grow-1 agar list menu mengisi sisa ruang --}}
                {{-- Contoh Grup Menu --}}
                <li class="nav-item mt-2 mb-1 px-2">
                    <small class="text-muted text-uppercase fw-semibold">Utama</small>
                </li>

                @foreach ($main_nav_items as $item)
                @if(Route::has($item['route_name'])) {{-- Cek apakah rute ada --}}
                <li class="nav-item mb-1">
                    <a class="nav-link py-2 px-3 rounded d-flex align-items-center {{ Str::is($item['active_pattern'], $current_route_name) ? 'active-nav-item' : 'text-dark link-hover' }}"
                        href="{{ route($item['route_name']) }}"
                        @if(Str::is($item['active_pattern'], $current_route_name)) aria-current="page" @endif>
                        <i class="bi {{ $item['icon'] }} fs-5 me-2"></i>
                        <span class="fw-medium">{{ $item['text'] }}</span>
                    </a>
                </li>
                @endif
                @endforeach

                {{-- Grup Menu untuk Admin --}}
                @if (!empty($admin_nav_items))
                <li class="nav-item mt-3 mb-1 px-2">
                    <small class="text-muted text-uppercase fw-semibold">Administrasi</small>
                </li>
                @foreach ($admin_nav_items as $item)
                @if(isset($item['is_modal']) && $item['is_modal'])
                <li class="nav-item mb-1">
                    <a class="nav-link py-2 px-3 rounded d-flex align-items-center text-dark link-hover"
                        href="#"
                        role="button"
                        data-bs-toggle="modal"
                        data-bs-target="{{ $item['modal_target'] }}">
                        <i class="bi {{ $item['icon'] }} fs-5 me-2"></i>
                        <span class="fw-medium">{{ $item['text'] }}</span>
                    </a>
                </li>
                @elseif(Route::has($item['route_name'])) {{-- Cek apakah rute ada --}}
                <li class="nav-item mb-1">
                    <a class="nav-link py-2 px-3 rounded d-flex align-items-center {{ Str::is($item['active_pattern'], $current_route_name) ? 'active-nav-item' : 'text-dark link-hover' }}"
                        href="{{ route($item['route_name']) }}"
                        @if(Str::is($item['active_pattern'], $current_route_name)) aria-current="page" @endif>
                        <i class="bi {{ $item['icon'] }} fs-5 me-2"></i>
                        <span class="fw-medium">{{ $item['text'] }}</span>
                    </a>
                </li>
                @endif
                @endforeach
                @endif
            </ul>
        </div>
    </nav>
</div>

{{-- Sidebar Offcanvas (untuk layar kecil) --}}
<div class="offcanvas offcanvas-start bg-light border-end d-lg-none" tabindex="-1" id="sidebarOffcanvasLayout" aria-labelledby="sidebarOffcanvasLayoutLabel">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title" id="sidebarOffcanvasLayoutLabel"><i class="bi bi-list-ul"></i> Menu Utama</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-2">
        <ul class="nav flex-column">
            <li class="nav-item mt-2 mb-1 px-2">
                <small class="text-muted text-uppercase fw-semibold">Utama</small>
            </li>
            @foreach ($main_nav_items as $item)
            @if(Route::has($item['route_name']))
            <li class="nav-item mb-1">
                <a class="nav-link py-2 px-3 rounded d-flex align-items-center {{ Str::is($item['active_pattern'], $current_route_name) ? 'active-nav-item-offcanvas' : 'text-dark link-hover-offcanvas' }}"
                    href="{{ route($item['route_name']) }}"
                    @if(Str::is($item['active_pattern'], $current_route_name)) aria-current="page" @endif>
                    <i class="bi {{ $item['icon'] }} fs-5 me-2"></i>
                    <span class="fw-medium">{{ $item['text'] }}</span>
                </a>
            </li>
            @endif
            @endforeach
            @if (!empty($admin_nav_items))
            <li class="nav-item mt-3 mb-1 px-2">
                <small class="text-muted text-uppercase fw-semibold">Administrasi</small>
            </li>
            @foreach ($admin_nav_items as $item)
            @if(isset($item['is_modal']) && $item['is_modal'])
            <li class="nav-item mb-1">
                <a class="nav-link py-2 px-3 rounded d-flex align-items-center text-dark link-hover-offcanvas"
                    href="#"
                    role="button"
                    data-bs-toggle="modal"
                    data-bs-target="{{ $item['modal_target'] }}">
                    <i class="bi {{ $item['icon'] }} fs-5 me-2"></i>
                    <span class="fw-medium">{{ $item['text'] }}</span>
                </a>
            </li>
            @elseif(Route::has($item['route_name']))
            <li class="nav-item mb-1">
                <a class="nav-link py-2 px-3 rounded d-flex align-items-center {{ Str::is($item['active_pattern'], $current_route_name) ? 'active-nav-item-offcanvas' : 'text-dark link-hover-offcanvas' }}"
                    href="{{ route($item['route_name']) }}"
                    @if(Str::is($item['active_pattern'], $current_route_name)) aria-current="page" @endif>
                    <i class="bi {{ $item['icon'] }} fs-5 me-2"></i>
                    <span class="fw-medium">{{ $item['text'] }}</span>
                </a>
            </li>
            @endif
            @endforeach
            @endif
        </ul>
    </div>
</div>

@push('styles')
<style>
    .active-nav-item {
        background-color: var(--bs-primary);
        color: var(--bs-white) !important;
        box-shadow: 0 .125rem .25rem rgba(0, 0, 0, .075);
    }

    .link-hover:hover {
        background-color: rgba(0, 0, 0, 0.05);
        color: var(--bs-emphasis-color);
    }

    .active-nav-item-offcanvas {
        background-color: var(--bs-primary);
        color: var(--bs-white) !important;
        box-shadow: 0 .125rem .25rem rgba(0, 0, 0, .075);
    }

    .link-hover-offcanvas:hover {
        background-color: rgba(0, 0, 0, 0.05);
        color: var(--bs-emphasis-color);
    }
</style>
@endpush
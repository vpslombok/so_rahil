@php
// Variabel $main_nav_items, $admin_nav_items, dan $current_route_name
// diasumsikan sudah tersedia dari scope layouts.sidebar karena di-include lebih dulu.
// Jika variabel ini tidak ada (misalnya, jika file ini di-include secara terpisah di masa depan),
// Anda mungkin perlu menambahkan fallback atau menggunakan View Composer.
// Untuk saat ini, kita asumsikan variabel tersebut ada.
$current_route_name_offcanvas = $current_route_name ?? Route::currentRouteName();
$_main_nav_items_offcanvas = $main_nav_items ?? [];
$_admin_nav_items_offcanvas = $admin_nav_items ?? [];
@endphp

<div class="offcanvas offcanvas-start bg-light border-end" tabindex="-1" id="sidebarOffcanvasLayout" aria-labelledby="sidebarOffcanvasLayoutLabel">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title" id="sidebarOffcanvasLayoutLabel"><i class="bi bi-list-ul"></i> Menu Utama</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-2"> {{-- Mengurangi padding agar mirip dengan px-2 di sidebar utama --}}
        <ul class="nav flex-column">
            {{-- Grup Menu Utama --}}
            <li class="nav-item mt-2 mb-1 px-2">
                <small class="text-muted text-uppercase fw-semibold">Utama</small>
            </li>
            @foreach ($_main_nav_items_offcanvas as $item)
                @if(Route::has($item['route_name']))
                    <li class="nav-item mb-1">
                        <a class="nav-link py-2 px-3 rounded d-flex align-items-center {{ Str::is($item['active_pattern'], $current_route_name_offcanvas) ? 'active-nav-item-offcanvas' : 'text-dark link-hover-offcanvas' }}"
                           href="{{ route($item['route_name']) }}"
                           @if(Str::is($item['active_pattern'], $current_route_name_offcanvas)) aria-current="page" @endif>
                            <i class="bi {{ $item['icon'] }} fs-5 me-2"></i>
                            <span class="fw-medium">{{ $item['text'] }}</span>
                        </a>
                    </li>
                @endif
            @endforeach

            {{-- Grup Menu untuk Admin --}}
            @if (!empty($_admin_nav_items_offcanvas))
                <li class="nav-item mt-3 mb-1 px-2">
                    <small class="text-muted text-uppercase fw-semibold">Administrasi</small>
                </li>
                @foreach ($_admin_nav_items_offcanvas as $item)
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
                    @elseif(isset($item['route_name']) && Route::has($item['route_name']))
                        <li class="nav-item mb-1">
                            <a class="nav-link py-2 px-3 rounded d-flex align-items-center {{ Str::is($item['active_pattern'], $current_route_name_offcanvas) ? 'active-nav-item-offcanvas' : 'text-dark link-hover-offcanvas' }}"
                               href="{{ route($item['route_name']) }}"
                               @if(Str::is($item['active_pattern'], $current_route_name_offcanvas)) aria-current="page" @endif>
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
.active-nav-item-offcanvas {
    background-color: var(--bs-primary); /* Menggunakan variabel Bootstrap untuk konsistensi */
    color: var(--bs-white) !important; /* Pastikan teks putih dan override text-dark */
    box-shadow: 0 .125rem .25rem rgba(0,0,0,.075);
}
.link-hover-offcanvas:hover {
    background-color: rgba(0,0,0,0.05); /* Efek hover yang lembut */
    color: var(--bs-emphasis-color);
}
</style>
@endpush
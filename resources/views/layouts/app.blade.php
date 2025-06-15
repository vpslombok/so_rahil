<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'Stok Opname')) - {{ config('app.name', 'Stok Opname') }}</title>

    {{-- Stylesheets --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <!-- Font Awesome (if using) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    {{-- Jika Anda menggunakan Vite untuk mengelola aset (disarankan di Laravel baru) --}}
    {{-- @vite(['resources/css/app.css', 'resources/js/app.js']) --}}

    {{-- Atau jika Anda memiliki style.css kustom di public/css --}}
    <!-- <link rel="stylesheet" href="{{ asset('css/style.css') }}"> -->
    {{-- DataTables Bootstrap 5 CSS --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">

    <style>
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background-color: #f8f9fa;
            /* Latar belakang default seperti di login */
        }

        .main-wrapper {
            display: flex;
            flex: 1;
        }

        .sidebar-wrapper {
            /* Wrapper untuk sidebar agar bisa sticky dengan benar */
            position: sticky;
            top: 0;
            /* Akan menempel di bagian atas viewport setelah navbar */
            height: calc(100vh - 56px); /* Tinggi viewport dikurangi tinggi navbar (sesuaikan 56px jika tinggi navbar Anda berbeda) */
            flex-shrink: 0;
            /* Mencegah sidebar menyusut */
            /* overflow-y sudah dihandle oleh <nav id="sidebarMenuMain"> di dalam sidebar.blade.php */
        }

        .content-wrapper {
            flex-grow: 1;
            /* Tinggi content-wrapper akan mengikuti tinggi .main-wrapper,
               dan .main-wrapper tingginya akan mengikuti tinggi viewport dikurangi navbar */
            height: calc(100vh - 56px); /* Sama dengan sidebar-wrapper untuk konsistensi */
            overflow-x: auto;
            /* Handle tabel lebar */
            /* Padding akan ditambahkan di dalam halaman konten, misal dengan container-fluid */
        }

        .navbar-laravel {
            /* Ganti nama kelas jika ada konflik */
            z-index: 1035; /* Lebih tinggi dari sidebar dan offcanvas backdrop */
            /* Lebih tinggi dari offcanvas backdrop */
        }

        .offcanvas-start {
            width: 280px;
            /* Lebar sidebar offcanvas */
        }

        /* Sticky top untuk navbar atas */
        .sticky-top {
            position: sticky;
            top: 0;
            z-index: 1030; /* Pastikan di atas konten lain tapi di bawah modal jika perlu */
            /* Pastikan di atas konten lain tapi di bawah modal jika perlu */
        }


        /* ... (style lain yang sudah ada) ... */

        /* Kustomisasi untuk pagination Laravel agar lebih kecil */
        .pagination {
            font-size: 0.875rem;
            /* Ukuran font sedikit lebih kecil */
        }

        .pagination .page-link {
            padding: 0.25rem 0.5rem;
            /* Padding lebih kecil untuk tombol halaman */
            line-height: 1.25;
            /* Sesuaikan line-height jika perlu */
        }

        .pagination .page-item.disabled .page-link {
            padding: 0.25rem 0.5rem;
            /* Pastikan padding konsisten untuk item disabled */
        }

        /* Jika Anda ingin ikon "previous" dan "next" juga lebih kecil (opsional) */
        .pagination .page-link[rel="prev"],
        .pagination .page-link[rel="next"] {
            /* Anda mungkin perlu menyesuaikan ikon bawaan atau menggunakan ikon font dengan ukuran lebih kecil */
        }

        /* Contoh: Mengubah warna pagination aktif dan link */
        .pagination .page-item.active .page-link {
            z-index: 3;
            color: #fff;
            background-color: #0d6efd;
            /* Warna primary Bootstrap */
            border-color: #0d6efd;
        }

        .pagination .page-link {
            color: #0d6efd;
            /* Warna link default */
        }

        .pagination .page-link:hover {
            color: #0a58ca;
            /* Warna link saat hover (lebih gelap) */
        }

        /* Page Loader Styles */
        .page-loader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background-color: rgba(255, 255, 255, 0.85); /* Latar belakang semi-transparan */
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999; /* Pastikan di atas segalanya */
            opacity: 1;
            transition: opacity 0.3s ease-out; /* Transisi untuk fade out */
        }
    </style>
    @stack('styles') {{-- Untuk CSS tambahan per halaman --}}
</head>

<body>
    {{-- Page Loader --}}
    <div id="page-loader" class="page-loader">
        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    @php
    // Definisikan variabel navigasi di sini agar tersedia untuk kedua sidebar
    $current_route_name = Route::currentRouteName();

    // Item Navigasi Utama (untuk semua user yang login)
    $main_nav_items = [
        ['route_name' => 'dashboard', 'active_pattern' => 'dashboard*', 'icon' => 'bi-speedometer2', 'text' => 'Dashboard Produk'],
        ['route_name' => 'stock_check.index', 'active_pattern' => 'stock_check.*', 'icon' => 'bi-clipboard-check-fill', 'text' => 'Entry Stok Fisik'],
        ['route_name' => 'so_by_selected.index', 'active_pattern' => 'so_by_selected.*', 'icon' => 'bi-check-square-fill', 'text' => 'Pilih SO'],
       ['route_name' => 'stock_audit_report.summary', 'active_pattern' => 'stock_audit_report.*', 'icon' => 'bi-file-earmark-text-fill', 'text' => 'Laporan Selisih'],
        ['route_name' => 'documentation', 'active_pattern' => 'documentation', 'icon' => 'bi-book-half', 'text' => 'Documentation'],
    ];

    // Item Navigasi Khusus Admin
    $admin_nav_items = [];
    if (Auth::check() && Auth::user()->role == 'admin') {
        $admin_nav_items = [
            ['route_name' => 'admin.users.index', 'active_pattern' => 'admin.users.*', 'icon' => 'bi-people-fill', 'text' => 'Manajemen User'],
            ['route_name' => 'admin.so-events.index', 'active_pattern' => 'admin.so-events.*', 'icon' => 'bi-calendar2-event-fill', 'text' => 'Manajemen SO'],
            ['route_name' => 'admin.so_monitor.index', 'active_pattern' => 'admin.so_monitor.*', 'icon' => 'bi-binoculars-fill', 'text' => 'Monitor SO'],
            ['route_name' => 'admin.racks.index', 'active_pattern' => 'admin.racks.*', 'icon' => 'bi-bookshelf', 'text' => 'Manajemen Rak'],
            ['route_name' => 'admin.api_log.index', 'active_pattern' => 'admin.api_log.*', 'icon' => 'bi-journal-text', 'text' => 'Log API'],
            ['route_name' => 'admin.flutter_app.manager', 'active_pattern' => 'admin.flutter_app.*', 'icon' => 'bi-phone-fill', 'text' => 'Upload Aplikasi Flutter'],
            ['route_name' => 'admin.database.utility', 'active_pattern' => 'admin.database.utility', 'icon' => 'bi-database-gear', 'text' => 'Utilitas Database'],
        ];
    }
    @endphp

    {{-- Navbar Atas --}}
    @include('layouts.navbar')

    <div class="main-wrapper">
        {{-- Sidebar --}}
        <div class="sidebar-wrapper d-none d-lg-block"> {{-- Hanya tampil di layar besar --}}
            @include('layouts.sidebar')
        </div>

        {{-- Konten Utama --}}
        <main class="content-wrapper">
            <div class="container-fluid p-3 p-md-4"> @yield('content') </div>
        </main>
    </div>

    {{-- Offcanvas Sidebar untuk layar kecil (dipanggil dari navbar) --}}
    @include('layouts.sidebar-offcanvas')

    {{-- Scripts --}}
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    {{-- DataTables JS --}}
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    {{-- DataTables Responsive JS --}}
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>

    <script>
        // Hide Page Loader when content is fully loaded
        window.addEventListener('load', function() {
            const loader = document.getElementById('page-loader');
            if (loader) {
                loader.style.opacity = '0'; // Mulai fade out
                setTimeout(() => {
                    loader.style.display = 'none'; // Sembunyikan setelah transisi selesai
                }, 300); // Sesuaikan dengan durasi transisi di CSS (0.3s)
            }
        });
    </script>
    @stack('scripts') {{-- Untuk JavaScript tambahan per halaman --}}
</body>

</html>
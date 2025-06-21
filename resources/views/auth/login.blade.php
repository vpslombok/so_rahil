<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login - {{ config('app.name', 'Stok Opname App') }}</title>

    <!-- Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <style>
        body {
            background: linear-gradient(135deg, #e3f2fd, #ffffff);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: 'Segoe UI', sans-serif;
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            padding: 30px;
            border-radius: 15px;
            background-color: #fff;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .login-card .form-control:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        }

        .logo-container {
            display: flex;
            justify-content: center;
            margin-bottom: 1.5rem;
        }

        .logo-container img {
            height: 160px;
            border-radius: 12px;
        }

        .input-group-text {
            background-color: #f0f0f0;
        }

        .btn-primary {
            font-size: 1rem;
            padding: 0.75rem;
        }

        .btn-success {
            font-size: 0.9rem;
        }

        .download-btn {
            margin-top: 1.5rem;
        }
    </style>
</head>

<body>

    <div class="login-card">
        <div class="card-body">
            <div class="logo-container">
                <img src="{{ asset('assets/stock_opname_logo.jpg') }}" alt="Logo" loading="lazy">
            </div>
            <h4 class="text-center mb-4">Login</h4>

            <form method="POST" action="{{ route('login.attempt') }}">
                @csrf
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person-circle"></i></span>
                        <input type="text" class="form-control @error('username') is-invalid @enderror"
                            id="username" name="username" value="{{ old('username') }}"
                            required autofocus placeholder="Masukkan username...">
                    </div>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                        <input type="password" class="form-control @error('password') is-invalid @enderror"
                            id="password" name="password" required placeholder="Masukkan password...">
                    </div>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">Masuk</button>
                </div>

                {{-- Menggunakan $active_flutter_app yang diasumsikan dikirim dari LoginController --}}
                @if (isset($active_flutter_app))
                <div class="text-center download-btn">
                    {{-- Mengarahkan ke rute download publik yang baru --}}
                    <a href="{{ route('app.public_download', ['version' => $active_flutter_app->id]) }}" class="btn btn-success">
                        <i class="bi bi-phone-arrow-down"></i> Download Aplikasi (v{{ $active_flutter_app->version_name }})
                    </a>
                </div>
                @endif
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Custom style untuk alert
        const swalCustomStyle = `
            .swal2-popup {
                border-radius: 12px !important;
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15) !important;
            }
            .swal2-title {
                font-size: 1.5rem !important;
                font-weight: 600 !important;
            }
            .swal2-html-container {
                font-size: 1.1rem !important;
                color: #4a5568 !important;
            }
            .swal2-confirm {
                border-radius: 8px !important;
                padding: 10px 24px !important;
                font-weight: 500 !important;
                transition: all 0.2s !important;
            }
            .swal2-confirm:hover {
                transform: translateY(-2px) !important;
                box-shadow: 0 4px 12px rgba(13, 110, 253, 0.3) !important;
            }
        `;

        // Tambahkan style custom ke DOM
        const style = document.createElement('style');
        style.innerHTML = swalCustomStyle;
        document.head.appendChild(style);

        // Error message dari session
        @if(session('error_message'))
        Swal.fire({
            icon: 'error',
            title: '<span style="color:#ef4444;">Oops!</span>',
            html: `<div class="alert-content" style="padding: 0.5rem 0;">
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <div style="font-size: 2.5rem; color: #ef4444;">⚠️</div>
                        <div style="font-size: 1.05rem; line-height: 1.5; color: #4a5568;">
                            {{ session('error_message') }}
                        </div>
                    </div>
                </div>`,
            showClass: {
                popup: 'animate__animated animate__zoomIn animate__faster'
            },
            hideClass: {
                popup: 'animate__animated animate__zoomOut animate__faster'
            },
            background: '#ffffff',
            confirmButtonColor: '#3b82f6',
            confirmButtonText: 'Mengerti',
            buttonsStyling: true,
            customClass: {
                container: 'custom-swal-container'
            }
        });
        @endif

        // Error validasi
        @if($errors->any())
        let errorMessages = '';
        @foreach($errors->all() as $error)
        errorMessages += `
            <div style="display: flex; align-items: flex-start; gap: 0.5rem; margin-bottom: 0.75rem;">
                <div style="color: #ef4444; font-size: 1.25rem;">•</div>
                <div style="color: #4a5568; font-size: 1rem; line-height: 1.4;">{{ $error }}</div>
            </div>`;
        @endforeach

        Swal.fire({
            icon: 'warning',
            title: '<span style="color: #f59e0b;">Perhatian!</span>',
            html: `
                <div style="text-align: left; margin-top: 1rem;">
                    <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem;">
                        <div style="font-size: 2.5rem;">🔍</div>
                        <div style="font-size: 1rem; color: #4a5568;">
                            Terdapat masalah dengan input yang Anda berikan
                        </div>
                    </div>
                    <div style="max-height: 200px; overflow-y: auto; padding-right: 0.5rem;">
                        ${errorMessages}
                    </div>
                </div>`,
            showClass: {
                popup: 'animate__animated animate__zoomIn animate__faster'
            },
            hideClass: {
                popup: 'animate__animated animate__zoomOut animate__faster'
            },
            background: '#ffffff',
            confirmButtonColor: '#3b82f6',
            confirmButtonText: 'Baik, akan diperbaiki',
            width: '500px',
            padding: '1.5rem',
            customClass: {
                container: 'custom-swal-container'
            }
        });
        @endif
    });
</script>

</body>

</html>
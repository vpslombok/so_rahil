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
                    <a href="{{ route('app.public_download', ['version' => $active_flutter_app->id]) }}" class="btn btn-success" download="{{ $active_flutter_app->file_name }}">
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
    document.addEventListener('DOMContentLoaded', function () {
        @if (session('error_message'))
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: "{{ session('error_message') }}",
                confirmButtonColor: '#0d6efd' // Warna tombol Bootstrap primary
            });
        @endif

        @if ($errors->any())
            let errorMessages = '';
            @foreach ($errors->all() as $error)
                errorMessages += `<li>{{ $error }}</li>`;
            @endforeach

            Swal.fire({
                icon: 'error',
                title: 'Terjadi Kesalahan Validasi',
                html: `<ul style="list-style-type: none; padding-left: 0; text-align: left;">${errorMessages}</ul>`,
                confirmButtonColor: '#0d6efd'
            });
        @endif
    });
</script>

</body>
</html>

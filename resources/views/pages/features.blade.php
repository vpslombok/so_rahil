@extends('layouts.app')

@section('title', 'Fitur Aplikasi dan Panduan Penggunaan')

@section('content')
<div class="container-fluid">
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white d-flex align-items-center">
            <i class="bi bi-stars me-3 fs-2"></i>
            <div>
                <h4 class="mb-0">Panduan Fitur & Penggunaan Aplikasi Stok Opname</h4>
                <small class="text-white-50">Versi {{ config('app.version', '1.0') }} &mdash; Update: {{ date('d M Y') }}</small>
            </div>
        </div>
        <div class="card-body">
            <p class="lead mb-4">Selamat datang di <strong>Aplikasi Stok Opname</strong>. Panduan ini menjelaskan fitur-fitur utama dan cara penggunaan aplikasi secara efektif untuk semua peran pengguna.</p>
            <div class="row g-4">
                <div class="col-lg-8">
                    <section id="dashboard-produk" class="mb-5">
                        <h5><i class="bi bi-speedometer2 me-2"></i>Dashboard Produk</h5>
                        <ul class="list-group list-group-flush mb-3">
                            <li class="list-group-item"><strong>Melihat Daftar Produk:</strong> Info lengkap produk (barcode, kode, nama, deskripsi, harga, stok).</li>
                            <li class="list-group-item"><strong>Pencarian & Filter:</strong> Cari produk, filter per user (admin), filter per event SO.</li>
                            <li class="list-group-item"><strong>Tambah/Edit/Hapus Produk:</strong> Tambah produk baru, edit detail/stok, hapus produk (admin).</li>
                            <li class="list-group-item"><strong>Import Excel (Admin):</strong> Import produk massal dari file Excel sesuai format.</li>
                        </ul>
                        <div class="alert alert-info small mb-0"><i class="bi bi-info-circle me-1"></i> Gunakan fitur pencarian dan filter untuk mempercepat pencatatan stok.</div>
                    </section>
                    <section id="pilih-so" class="mb-5">
                        <h5><i class="bi bi-check-square-fill me-2"></i>Pilih SO (Stock Opname Event)</h5>
                        <ol class="ps-3 mb-2">
                            <li>Pilih event SO dari dropdown.</li>
                            <li>Daftar produk event akan tampil otomatis.</li>
                            <li>Klik <span class="badge bg-primary">Persiapkan untuk Entri</span> untuk mulai entri stok.</li>
                        </ol>
                        <div class="alert alert-warning small mb-0"><i class="bi bi-exclamation-triangle me-1"></i> Hanya satu SO Event aktif per hari. Selesaikan/finalisasi sebelum mulai event lain.</div>
                    </section>
                    <section id="entry-stok" class="mb-5">
                        <h5><i class="bi bi-clipboard-check-fill me-2"></i>Entry Stok Fisik</h5>
                        <ol class="ps-3 mb-2">
                            <li>Input stok fisik pada kolom yang tersedia.</li>
                            <li>Gunakan fitur cari barcode/kode produk untuk update cepat.</li>
                            <li>Klik <span class="badge bg-success">Simpan Stok Fisik</span> jika sudah selesai.</li>
                            <li>Jika salah, klik <span class="badge bg-secondary">Reset</span> pada produk terkait.</li>
                        </ol>
                    </section>
                    <section id="lihat-selisih" class="mb-5">
                        <h5><i class="bi bi-file-earmark-diff-fill me-2"></i>Lihat Selisih</h5>
                        <ul class="list-group list-group-flush mb-2">
                            <li class="list-group-item">Bandingkan stok sistem & stok fisik.</li>
                            <li class="list-group-item">Lihat produk yang selisih, koreksi jika perlu.</li>
                            <li class="list-group-item">Lanjutkan ke <span class="badge bg-primary">Finalisasi SO</span> jika data sudah benar.</li>
                        </ul>
                    </section>
                    <section id="finalisasi-so" class="mb-5">
                        <h5><i class="bi bi-check-circle-fill me-2"></i>Finalisasi SO</h5>
                        <ol class="ps-3 mb-2">
                            <li>Klik <span class="badge bg-primary">Finalisasi SO</span> setelah cek selisih.</li>
                            <li>Data terkunci & masuk audit. Tidak bisa diubah lagi.</li>
                            <li>Bisa mulai event SO baru setelah finalisasi.</li>
                        </ol>
                    </section>
                    <section id="laporan-selisih" class="mb-5">
                        <h5><i class="bi bi-file-earmark-text-fill me-2"></i>Laporan Selisih</h5>
                        <ul class="list-group list-group-flush mb-2">
                            <li class="list-group-item">Lihat ringkasan semua sesi SO yang sudah difinalisasi.</li>
                            <li class="list-group-item">Admin bisa lihat semua laporan, user hanya miliknya.</li>
                            <li class="list-group-item">Klik <span class="badge bg-info">Detail</span> untuk rincian produk, stok, dan selisih.</li>
                        </ul>
                    </section>
                </div>
                <div class="col-lg-4">
                    <div class="alert alert-primary mb-4">
                        <div class="fw-bold mb-1"><i class="bi bi-lightbulb me-1"></i>Tips Penggunaan Cepat</div>
                        <ul class="mb-0 ps-3">
                            <li>Gunakan fitur pencarian untuk menemukan produk lebih cepat.</li>
                            <li>Pastikan event SO sudah dipilih sebelum entri stok.</li>
                            <li>Selalu cek selisih sebelum finalisasi.</li>
                            <li>Gunakan fitur import untuk input produk massal.</li>
                        </ul>
                    </div>
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-secondary text-white py-2"><i class="bi bi-shield-lock me-2"></i>Keamanan Data</div>
                        <div class="card-body small">
                            <ul class="mb-0 ps-3">
                                <li>Setiap perubahan data tercatat di sistem (audit trail).</li>
                                <li>Hanya user dengan hak akses yang bisa mengedit/hapus data penting.</li>
                                <li>Backup database secara berkala untuk keamanan maksimal.</li>
                            </ul>
                        </div>
                    </div>
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-info text-white py-2"><i class="bi bi-question-circle me-2"></i>Bantuan</div>
                        <div class="card-body small">
                            <ul class="mb-0 ps-3">
                                <li>Hubungi admin jika ada kendala login atau data.</li>
                                <li>Lihat menu <b>Panduan</b> untuk tutorial lebih detail.</li>
                                <li>Gunakan fitur <b>Reset Password</b> jika lupa sandi.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            @if(Auth::check() && Auth::user()->role == 'admin')
            <hr>
            <h4 class="mt-5 mb-3 text-danger"><i class="bi bi-shield-lock-fill me-2"></i>Fitur Khusus Admin</h4>
            <div class="row g-4">
                <div class="col-lg-6">
                    <section id="manajemen-user-admin" class="mb-4">
                        <h5><i class="bi bi-people-fill me-2"></i>Manajemen User</h5>
                        <ul class="list-group list-group-flush mb-2">
                            <li class="list-group-item">Lihat, tambah, edit, dan hapus user/operator.</li>
                        </ul>
                    </section>
                    <section id="manajemen-so-admin" class="mb-4">
                        <h5><i class="bi bi-calendar2-event-fill me-2"></i>Manajemen SO (Stock Opname Event)</h5>
                        <ul class="list-group list-group-flush mb-2">
                            <li class="list-group-item">Buat, edit, hapus event SO.</li>
                            <li class="list-group-item">Kelola produk dalam event SO.</li>
                        </ul>
                    </section>
                </div>
                <div class="col-lg-6">
                    <section id="utilitas-admin" class="mb-4">
                        <h5><i class="bi bi-tools me-2"></i>Utilitas Admin Lainnya</h5>
                        <ul class="list-group list-group-flush mb-2">
                            <li class="list-group-item">Log API (jika diaktifkan).</li>
                            <li class="list-group-item">Upload & kelola aplikasi Flutter (jika diaktifkan).</li>
                            <li class="list-group-item">Utilitas database: backup, restore, migrate, dll.</li>
                        </ul>
                    </section>
                </div>
            </div>
            @endif

            <hr>
            <p class="text-center text-muted mt-4">Jika Anda memiliki pertanyaan lebih lanjut atau menemukan masalah, silakan hubungi administrator sistem.</p>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    section h5 {
        color: var(--bs-primary);
        margin-top: 1.5rem;
        margin-bottom: 0.75rem;
        border-bottom: 2px solid var(--bs-gray-300);
        padding-bottom: 0.25rem;
    }

    section h6 {
        font-weight: bold;
        margin-top: 1rem;
    }
</style>
@endpush
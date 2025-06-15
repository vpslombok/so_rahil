@extends('layouts.app')

@section('title', 'Fitur Aplikasi dan Panduan Penggunaan')

@section('content')
<div class="container-fluid">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">Panduan Fitur Aplikasi Stok Opname</h4>
        </div>
        <div class="card-body">
            <p class="lead">Selamat datang di panduan penggunaan Aplikasi Stok Opname. Halaman ini akan menjelaskan fitur-fitur utama aplikasi dan bagaimana cara menggunakannya secara efektif.</p>
            <hr>

            <section id="dashboard-produk" class="mb-5">
                <h5><i class="bi bi-speedometer2 me-2"></i>Dashboard Produk</h5>
                <p>Dashboard Produk adalah halaman utama setelah Anda login. Di sini Anda dapat melihat daftar semua produk yang terdaftar di sistem.</p>
                <h6>Fitur Utama:</h6>
                <ul>
                    <li><strong>Melihat Daftar Produk:</strong> Menampilkan informasi produk seperti Barcode, Kode Produk, Nama, Deskripsi, Harga, dan Stok (jika berlaku untuk user Anda atau jika Anda admin yang memfilter user).</li>
                    <li><strong>Pencarian Produk:</strong> Cari produk dengan cepat berdasarkan nama, kode produk, atau barcode.</li>
                    <li><strong>Filter Stok per User (Admin):</strong> Admin dapat melihat stok produk untuk pengguna tertentu.</li>
                    <li><strong>Filter Produk per Event SO (Admin & User):</strong> Anda dapat memfilter produk yang termasuk dalam Stock Opname Event tertentu.</li>
                    <li><strong>Tambah Produk Baru:</strong> Pengguna (tergantung hak akses) dapat menambahkan produk baru ke sistem.
                        <ul>
                            <li>Klik tombol "Tambah Produk".</li>
                            <li>Isi detail produk seperti Barcode, Kode Produk, Nama, Deskripsi, dan Harga.</li>
                            <li>Klik "Simpan".</li>
                        </ul>
                    </li>
                    <li><strong>Edit Produk & Stok:</strong>
                        <ul>
                            <li>Klik ikon pensil <i class="bi bi-pencil-fill"></i> pada baris produk yang ingin diedit.</li>
                            <li>Ubah informasi produk atau jumlah stok (jika Anda admin atau stok tersebut milik Anda).</li>
                            <li>Klik "Simpan Perubahan".</li>
                        </ul>
                    </li>
                    <li><strong>Hapus Produk (Admin):</strong> Admin dapat menghapus produk dari sistem.
                        <ul>
                            <li>Klik ikon tong sampah <i class="bi bi-trash-fill"></i> pada baris produk.</li>
                            <li>Konfirmasi penghapusan.</li>
                        </ul>
                    </li>
                    <li><strong>Import Produk dari Excel (Admin):</strong> Admin dapat mengimpor daftar produk secara massal menggunakan file Excel.
                        <ul>
                            <li>Klik menu "Import Excel" di sidebar (jika Anda admin).</li>
                            <li>Pilih file Excel yang sesuai format.</li>
                            <li>Klik "Import". Pastikan kolom di Excel sesuai dengan format yang dibutuhkan (misalnya: barcode, product_code, name, description, price).</li>
                        </ul>
                    </li>
                </ul>
            </section>

            <section id="pilih-so" class="mb-5">
                <h5><i class="bi bi-check-square-fill me-2"></i>Pilih SO (Stock Opname Event)</h5>
                <p>Menu ini digunakan untuk memilih Stock Opname Event yang aktif sebelum Anda dapat melakukan entri stok fisik.</p>
                <h6>Cara Penggunaan:</h6>
                <ol>
                    <li>Pilih SO Event yang tersedia dari dropdown.</li>
                    <li>Setelah memilih, daftar produk yang termasuk dalam SO Event tersebut akan ditampilkan.</li>
                    <li>Jika Anda siap untuk memulai entri stok untuk event tersebut, klik tombol "Persiapkan untuk Entri".</li>
                    <li><strong>Penting:</strong>
                        <ul>
                            <li>Anda hanya dapat mengerjakan satu SO Event pada satu waktu per hari. Jika Anda memiliki SO Event lain yang belum difinalisasi pada hari yang sama, Anda harus menyelesaikannya terlebih dahulu.</li>
                            <li>Jika Anda melanjutkan sesi SO yang sudah ada pada hari yang sama untuk event yang sama, sistem akan otomatis melanjutkan nomor nota yang ada.</li>
                            <li>Jika Anda memulai SO baru untuk event yang sama (setelah finalisasi sebelumnya) atau event yang berbeda (setelah finalisasi event lain), sistem akan membuat nomor nota baru.</li>
                        </ul>
                    </li>
                </ol>
            </section>

            <section id="entry-stok" class="mb-5">
                <h5><i class="bi bi-clipboard-check-fill me-2"></i>Entry Stok Fisik</h5>
                <p>Setelah memilih dan mempersiapkan SO Event, Anda akan diarahkan ke halaman ini untuk memasukkan jumlah stok fisik yang Anda hitung.</p>
                <h6>Cara Penggunaan:</h6>
                <ol>
                    <li>Halaman akan menampilkan daftar produk yang perlu dihitung untuk SO Event dan Nomor Nota yang aktif.</li>
                    <li>Masukkan jumlah stok fisik yang sebenarnya Anda temukan di lapangan pada kolom "Stok Fisik".</li>
                    <li>Anda dapat menggunakan fitur "Cari & Update Cepat" dengan memasukkan barcode atau kode produk untuk langsung menuju ke produk tersebut dan mengupdate stoknya.</li>
                    <li>Setelah semua produk yang relevan dihitung dan stok fisiknya dimasukkan, klik tombol "Simpan Stok Fisik".</li>
                    <li>Jika ada kesalahan input, Anda dapat mengklik tombol "Reset" pada baris produk untuk mengosongkan input stok fisik produk tersebut.</li>
                    <li>Setelah menyimpan, Anda akan diarahkan ke halaman "Lihat Selisih".</li>
                </ol>
            </section>

            <section id="lihat-selisih" class="mb-5">
                <h5><i class="bi bi-file-earmark-diff-fill me-2"></i>Lihat Selisih (Sebelum Finalisasi)</h5>
                <p>Halaman ini muncul setelah Anda menyimpan entri stok fisik. Di sini Anda dapat melihat perbandingan antara stok sistem dan stok fisik yang baru saja Anda masukkan.</p>
                <h6>Fitur Utama:</h6>
                <ul>
                    <li>Menampilkan daftar produk yang memiliki selisih (stok sistem tidak sama dengan stok fisik).</li>
                    <li>Menunjukkan jumlah selisih untuk setiap produk.</li>
                    <li>Jika semua sudah sesuai, Anda dapat melanjutkan ke proses "Finalisasi SO" dengan mengklik tombol yang tersedia.</li>
                    <li>Jika ada yang perlu diperbaiki, Anda bisa kembali ke halaman "Entry Stok Fisik" untuk mengoreksi data.</li>
                </ul>
            </section>

            <section id="finalisasi-so" class="mb-5">
                <h5><i class="bi bi-check-circle-fill me-2"></i>Finalisasi SO</h5>
                <p>Proses finalisasi akan mengunci data stok opname untuk Nomor Nota tersebut dan menyimpannya sebagai catatan audit.</p>
                <h6>Cara Penggunaan:</h6>
                <ol>
                    <li>Setelah memeriksa halaman "Lihat Selisih" dan yakin data sudah benar, klik tombol "Finalisasi SO (Nota: [Nomor Nota])".</li>
                    <li>Sistem akan menyimpan data selisih ke dalam tabel audit.</li>
                    <li>Setelah finalisasi, Anda tidak dapat lagi mengubah entri stok untuk Nomor Nota tersebut.</li>
                    <li>Anda kemudian dapat memulai SO Event baru atau melanjutkan SO Event lain jika ada.</li>
                </ol>
            </section>

            <section id="laporan-selisih" class="mb-5">
                <h5><i class="bi bi-file-earmark-text-fill me-2"></i>Laporan Selisih</h5>
                <p>Menu ini menampilkan ringkasan dari semua sesi stok opname yang telah difinalisasi, dikelompokkan per nomor nota.</p>
                <h6>Fitur Utama:</h6>
                <ul>
                    <li>Menampilkan daftar Nomor Nota yang sudah difinalisasi, beserta informasi Event SO, User yang melakukan finalisasi, dan Tanggal Finalisasi.</li>
                    <li>Admin dapat melihat semua laporan. Pengguna non-admin secara default melihat laporan mereka sendiri, atau dapat difilter jika ada parameter `user_id` di URL (tergantung implementasi).</li>
                    <li>Klik tombol "Detail" pada setiap Nomor Nota untuk melihat rincian item-item produk yang diaudit dalam sesi finalisasi tersebut, termasuk stok sistem, stok fisik, dan selisihnya.</li>
                </ul>
            </section>

            @if(Auth::check() && Auth::user()->role == 'admin')
            <hr>
            <h4 class="mt-5 mb-3 text-danger">Fitur Khusus Admin</h4>

            <section id="manajemen-user-admin" class="mb-5">
                <h5><i class="bi bi-people-fill me-2"></i>Manajemen User</h5>
                <p>Admin dapat mengelola akun pengguna lain di sistem.</p>
                <h6>Fitur Utama:</h6>
                <ul>
                    <li>Melihat daftar semua pengguna.</li>
                    <li>Menambah pengguna baru (misalnya, operator stok opname).</li>
                    <li>Mengedit detail pengguna (username, password, peran).</li>
                    <li>Menghapus pengguna.</li>
                </ul>
            </section>

            <section id="manajemen-so-admin" class="mb-5">
                <h5><i class="bi bi-calendar2-event-fill me-2"></i>Manajemen SO (Stock Opname Event)</h5>
                <p>Admin dapat membuat dan mengelola Stock Opname Event.</p>
                <h6>Fitur Utama:</h6>
                <ul>
                    <li><strong>Membuat Event SO Baru:</strong>
                        <ul>
                            <li>Tentukan Nama Event, Tanggal Mulai, Tanggal Selesai (opsional), dan Status (misalnya, 'active', 'inactive', 'completed').</li>
                        </ul>
                    </li>
                    <li><strong>Mengedit Event SO:</strong> Ubah detail event yang sudah ada.</li>
                    <li><strong>Menghapus Event SO:</strong> Hapus event SO (dengan batasan, misalnya tidak bisa dihapus jika sudah ada data finalisasi terkait).</li>
                    <li><strong>Mengelola Produk dalam Event SO:</strong>
                        <ul>
                            <li>Setelah event dibuat, klik tombol "Lihat Detail & Produk" (ikon mata <i class="fas fa-eye"></i>).</li>
                            <li>Di halaman detail event, Anda dapat memilih produk mana saja yang akan dimasukkan ke dalam SO Event tersebut.</li>
                            <li>Hapus produk dari SO Event jika tidak lagi relevan.</li>
                        </ul>
                    </li>
                </ul>
            </section>

            <section id="utilitas-admin" class="mb-5">
                <h5><i class="bi bi-tools me-2"></i>Utilitas Admin Lainnya</h5>
                <p>Admin memiliki akses ke beberapa fitur utilitas tambahan:</p>
                <ul>
                    <li><strong>Log API:</strong> (Jika diimplementasikan) Melihat log interaksi API.</li>
                    <li><strong>Upload Aplikasi Flutter:</strong> (Jika diimplementasikan) Mengelola versi aplikasi mobile.</li>
                    <li><strong>Utilitas Database:</strong> (Jika diimplementasikan) Alat bantu untuk manajemen database.</li>
                </ul>
            </section>
            @endif

            <hr>
            <p class="text-center text-muted">Jika Anda memiliki pertanyaan lebih lanjut atau menemukan masalah, silakan hubungi administrator sistem.</p>
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
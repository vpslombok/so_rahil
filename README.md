<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

# SO Rahil - Stock Opname Event & Flutter App Management

Aplikasi ini adalah sistem manajemen Stock Opname Event (SO Event) berbasis Laravel, dengan fitur backend dan frontend untuk proses stock opname, pelaporan, serta manajemen file APK aplikasi Flutter.

## Fitur Utama
- Manajemen event Stock Opname (buat, aktifkan, selesaikan, batalkan)
- Entry stok fisik produk per event SO
- Finalisasi dan laporan hasil SO Event
- API untuk frontend dan aplikasi mobile
- Upload, download, dan manajemen file APK Flutter
- Hak akses admin/user

## Instalasi
1. **Clone repository**
2. **Install dependency**
   ```bash
   composer install
   npm install && npm run build
   ```
3. **Copy .env**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
4. **Konfigurasi database** di file `.env`
5. **Migrasi dan seeder**
   ```bash
   php artisan migrate:fresh --seed
   ```
   Seeder default akan membuat user admin:
   - Username: `admin`
   - Password: `123123`

## Manajemen APK Flutter
- Upload APK melalui menu admin (route: `/admin/flutter-app`)
- File APK akan tersimpan di: `storage/app/flutter_apks/`
- Download APK publik: endpoint tersedia untuk aplikasi mobile

## API Utama
- Endpoint SO Event: entry, finalisasi, hapus, laporan
- Endpoint APK: versi terbaru, download APK

## Struktur Folder Penting
- `app/Http/Controllers/` : Controller API, admin, dan frontend
- `app/Models/` : Model Eloquent
- `database/migrations/` : Migrasi tabel
- `database/seeders/` : Seeder data awal
- `resources/views/` : Blade template
- `storage/app/flutter_apks/` : Lokasi file APK Flutter

## Pengembangan
- Laravel 10+
- TailwindCSS untuk frontend
- Mendukung integrasi aplikasi Flutter

## Catatan
- Pastikan permission folder `storage/app/flutter_apks` dapat ditulis web server.
- Untuk upload APK, maksimal 100MB per file.

---

Aplikasi ini dikembangkan untuk kebutuhan internal manajemen stok dan distribusi aplikasi mobile. Untuk kontribusi atau pertanyaan, silakan hubungi admin proyek.

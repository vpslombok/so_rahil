<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth; // Ditambahkan untuk rute logout
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\SoSelectedProductsController;
use App\Http\Controllers\Admin\StockOpnameEventController; // Tambahkan ini
use App\Http\Controllers\StockCheckController; // Tambahkan ini
use App\Http\Controllers\ReportController; // Tambahkan ReportController
use App\Http\Controllers\Admin\RackController; // Tambahkan RackController
use App\Http\Controllers\SoBySelectedController;
use App\Http\Controllers\PageController; // Tambahkan PageController
use App\Http\Controllers\Admin\FlutterAppController; // Tambahkan FlutterAppController
use App\Http\Controllers\Admin\DatabaseUtilityController; // Tambahkan DatabaseUtilityController
use App\Http\Controllers\Admin\SoMonitorController; // Tambahkan SoMonitorController

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|*/

//======================================================================
// RUTE AUTENTIKASI
//======================================================================
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.attempt');
Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/');
})->name('logout')->middleware('auth');

//======================================================================
// RUTE PUBLIK LAINNYA (Contoh: Download Aplikasi)
//======================================================================
Route::get('/app/download/{version}', [FlutterAppController::class, 'publicDownload'])->name('app.public_download');

//======================================================================
// RUTE YANG MEMERLUKAN AUTENTIKASI (SEMUA USER)
//======================================================================
Route::middleware(['auth'])->group(function () {

    //------------------------------------------------------------------
    // Dashboard Utama
    //------------------------------------------------------------------
    Route::get('/', [ProductController::class, 'index'])->name('dashboard');
    // Komentar: Rute dashboard di atas sudah mencakup fungsionalitas ini.
    //------------------------------------------------------------------
    // Manajemen Produk (Pengguna Umum)
    //------------------------------------------------------------------
    Route::prefix('products')->name('products.')->group(function () {
        Route::post('/', [ProductController::class, 'store'])->name('store');
        Route::put('/{product}', [ProductController::class, 'update'])->name('update');
        Route::delete('/{product}', [ProductController::class, 'destroy'])->name('destroy');

        Route::get('/import', function () { // Form untuk import
            return "Halaman Import Excel Produk (products.import.form)"; // Sebaiknya ke view
            // Contoh: return view('products.import');
        })->name('import.form');
        Route::post('/import', [ProductController::class, 'importExcel'])->name('import.submit');
    });

    //------------------------------------------------------------------
    // Pengecekan Stok & Stock Opname (Pengguna Umum)
    //------------------------------------------------------------------
    Route::prefix('stock-check')->name('stock_check.')->group(function () {
        // Rute untuk AJAX find product dan update single stock (harus sebelum rute dengan parameter)
        Route::get('/find-product-for-entry', [StockCheckController::class, 'findProductForEntry'])->name('find_product_for_entry');
        Route::post('/update-single-stock', [StockCheckController::class, 'updateSingleStock'])->name('update_single_stock');
        Route::post('/reset-single-stock', [StockCheckController::class, 'resetSingleStock'])->name('reset_single_stock');

        // Menggunakan {stock_opname_event?} untuk route model binding ke StockOpnameEvent model
        Route::get('/{stock_opname_event?}', [StockCheckController::class, 'index'])->name('index');
        Route::post('/', [StockCheckController::class, 'store'])->name('store');
        // Mengubah rute untuk showDifferences agar nomor_nota menjadi parameter path
        Route::get('/differences/{nomor_nota}', [StockCheckController::class, 'showDifferences'])->name('show_differences');
        // Route untuk update physical stock from differences juga bisa disederhanakan jika nomor_nota cukup
        Route::post('/update-physical-stock-from-differences/{nomor_nota}', [StockCheckController::class, 'updatePhysicalStockFromDifferences'])->name('update_physical_stock_from_differences');
        Route::post('/finalize/{nomor_nota}', [StockCheckController::class, 'finalize'])->name('finalize_event');
    });

    //------------------------------------------------------------------
    // Stock Opname Berdasarkan Event Terpilih (Pengguna Umum)
    //------------------------------------------------------------------
    Route::prefix('so-by-selected')->name('so_by_selected.')->group(function () {
        Route::get('/', [SoBySelectedController::class, 'index'])->name('index');
        Route::post('/', [SoBySelectedController::class, 'index'])->name('select_event'); // Menangani submit pemilihan event
        // Route::post('so-by-selected', [SoBySelectedController::class, 'store'])->name('so_by_selected.store'); // Dikomentari karena fungsionalitasnya tidak jelas
        Route::post('/prepare-for-entry', [SoBySelectedController::class, 'prepareForEntry'])->name('prepare_for_entry');
    });

    //------------------------------------------------------------------
    // Rute Lain-lain (Pengguna Umum)
    //------------------------------------------------------------------
    Route::get('/stock-audit-report', [ReportController::class, 'finalizedStockOpnameReport'])->name('stock_audit_report.index');
    Route::get('/stock-audit-report', [ReportController::class, 'finalizedStockOpnameReport'])->name('stock_audit_report.summary'); // Ganti nama rute
    Route::delete('/stock-audit-report/delete', [ReportController::class, 'destroyFinalizedStockOpnameGroup'])->name('stock_audit_report.destroy_group');
    Route::get('/stock-audit-report/{nomor_nota}', [ReportController::class, 'showFinalizedStockOpnameDetailsByNota'])->name('stock_audit_report.details_by_nota');

    Route::get('/application-features', [PageController::class, 'applicationFeatures'])->name('documentation'); // Rute tetap 'documentation' agar sidebar tidak perlu diubah

    //======================================================================
    // RUTE KHUSUS ADMIN (MEMERLUKAN MIDDLEWARE 'admin')
    //======================================================================
    Route::middleware(['admin'])->prefix('admin')->name('admin.')->group(function () {
        //------------------------------------------------------------------
        // Manajemen Rak (Admin)
        //------------------------------------------------------------------
        Route::resource('racks', RackController::class); // Akan menjadi admin.racks.index, dll.

        //------------------------------------------------------------------
        // Manajemen User (Admin)
        //------------------------------------------------------------------
        Route::resource('users', UserController::class)->except(['show']); // Akan menjadi admin.users.index, dll.

        //------------------------------------------------------------------
        // Manajemen Stock Opname Event (Admin)
        //------------------------------------------------------------------
        Route::resource('so-events', StockOpnameEventController::class); // Menghasilkan admin.so-events.* (termasuk .destroy)

        //------------------------------------------------------------------
        // Monitor Stock Opname (Admin)
        //------------------------------------------------------------------
        Route::get('/so-monitor', [SoMonitorController::class, 'index'])->name('so_monitor.index');
        Route::delete('/so-monitor/session', [SoMonitorController::class, 'destroySession'])->name('so_monitor.destroy_session');

        //------------------------------------------------------------------
        // Manajemen Produk Terpilih untuk SO Event (Admin)
        //------------------------------------------------------------------
        Route::prefix('so-events/{so_event}/products')->name('so-events.products.')->group(function () {
            // Menambah produk ke SO Event tertentu
            Route::post('/', [SoSelectedProductsController::class, 'store'])->name('store'); // admin.so-events.products.store
        });
        // Menghapus beberapa produk terpilih sekaligus dari SO Event
        Route::delete('so-selected-products/bulk-destroy', [SoSelectedProductsController::class, 'bulkDestroy'])->name('so_selected_products.bulkDestroy'); // admin.so_selected_products.bulkDestroy
        // Menghapus produk spesifik yang terpilih dari SO Event
        Route::delete('so-selected-products/{soSelectedProduct}', [SoSelectedProductsController::class, 'destroy'])->name('so_selected_products.destroy'); // admin.so_selected_products.destroy

        //------------------------------------------------------------------
        // Management Aplikasi Flutter (Admin)
        //------------------------------------------------------------------
        Route::get('/flutter-app-manager', [FlutterAppController::class, 'manager'])->name('flutter_app.manager');
        Route::post('/flutter-app-manager/upload', [FlutterAppController::class, 'upload'])->name('flutter_app.upload');
        Route::post('/flutter-app-manager/delete', [FlutterAppController::class, 'delete'])->name('flutter_app.delete');
        Route::get('/flutter-app-manager/download', [FlutterAppController::class, 'download'])->name('flutter_app.download');
        Route::get('/flutter-app-manager/versions', [FlutterAppController::class, 'versions'])->name('flutter_app.versions');
        Route::post('/flutter-app-manager/set-active-version', [FlutterAppController::class, 'setActiveVersion'])->name('flutter_app.set_active_version');
        Route::post('/flutter-app-manager/deactivate-version', [FlutterAppController::class, 'deactivateVersion'])->name('flutter_app.deactivate_version');

        //------------------------------------------------------------------
        // Management Database Utility (Admin)
        //------------------------------------------------------------------
        Route::get('/database-utility', [DatabaseUtilityController::class, 'index'])
            ->name('database.utility'); // Halaman utama

        // Route untuk backup database
        Route::get('/database-utility/backup/create', [DatabaseUtilityController::class, 'createBackup'])
            ->name('database.backup.create');
        Route::get('/database-utility/backup/download/{filename}', [DatabaseUtilityController::class, 'downloadBackup'])
            ->name('database.backup.download');
        Route::post('/database-utility/backup/restore/{filename}', [DatabaseUtilityController::class, 'restoreBackup'])
            ->name('database.backup.restore');
        Route::delete('/database-utility/backup/delete/{filename}', [DatabaseUtilityController::class, 'deleteBackup'])
            ->name('database.backup.delete');
        Route::get('/database-utility/migrate', [DatabaseUtilityController::class, 'runMigration'])
            ->name('database.migrate');

        Route::get('/database-utility/tables/{table}/data', [DatabaseUtilityController::class, 'showTableData'])
            ->name('database.table.data'); // Lihat data tabel

        Route::post('/database-utility/tables', [DatabaseUtilityController::class, 'storeTable'])
            ->name('database.table.store'); // Buat tabel baru

        Route::delete('/database-utility/tables/{table}', [DatabaseUtilityController::class, 'destroyTable'])
            ->name('database.table.destroy'); // Hapus tabel
        //------------------------------------------------------------------
        // Rute untuk halaman log API (admin.api_log.index)
        //------------------------------------------------------------------
        Route::get('/api-log', function () { // Akan menjadi admin.api_log.index
            return "Halaman Log API (admin.api_log.index)";
        })->name('api_log');
    });
});

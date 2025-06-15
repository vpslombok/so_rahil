<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Admin\FlutterAppController; // Tambahkan ini untuk controller aplikasi Flutter
use App\Http\Controllers\Api\SoSelectionController; // Tambahkan controller SO Selection
use App\Http\Controllers\Api\SelectableSoEventController; // Tambahkan controller baru
use App\Http\Controllers\Api\UserStockController; // Tambahkan ini
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/
// ----------------------------------------------------
// Rute untuk otentikasi
// ----------------------------------------------------
Route::post('/login', [AuthController::class, 'login']);

// ----------------------------------------------------
// Rute untuk mengelola stok produk pengguna
// (entri stock opname yang diinput oleh pengguna)
// ----------------------------------------------------
// Rute yang memerlukan otentikasi
// ----------------------------------------------------
Route::middleware('auth:sanctum')->group(function () {
    // ----------------------------------------------------
    // Rute Otentikasi (dalam grup auth)
    // ----------------------------------------------------
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // ----------------------------------------------------
    // Rute Stok Produk Pengguna (dalam grup auth)
    // ----------------------------------------------------
    Route::prefix('me/stock-entries') // "me" mengindikasikan data milik pengguna yang terotentikasi
        ->controller(UserStockController::class) // Menggunakan grup controller untuk definisi rute yang lebih bersih
        ->name('api.me.stock-entries.') // Memberikan prefix nama untuk rute
        ->group(function () {
            /**
             * Mengambil daftar entri stok yang telah diinput oleh pengguna.
             * Dapat difilter berdasarkan 'stock_opname_event_id'.
             * GET /api/me/stock-entries
             */
            Route::get('/', 'getProductStock')->name('index');

            /**
             * Membuat atau memperbarui entri stok produk oleh pengguna untuk event SO tertentu.
             * POST /api/me/stock-entries
             */
            Route::post('/', 'updateStock')->name('storeOrUpdate');

            /**
             * Mengambil daftar produk yang belum diinput stoknya oleh pengguna untuk event SO aktif.
             * GET /api/me/stock-entries/pending-for-active-event
             */
            Route::get('/pending-for-active-event', 'getPendingSubmissionsForActiveEvent')->name('pending');

            /**
             * Mengambil data opsi filter (rak dan event SO aktif).
             * GET /api/me/stock-entries/filter-options
             */
            Route::get('/filter-options', 'getFilterOptions')->name('filterOptions');

            /**
             * Memperbarui atau membuat entri stok sementara (TempStockEntry) untuk user login.
             * POST /api/me/stock-entries/update
             */
            Route::post('/update', [SelectableSoEventController::class, 'updateStockEntry'])->name('update');

            /**
             * Simpan data produk berdasarkan item SO Event (bulk insert/update stok fisik untuk semua produk pada event terpilih).
             * POST /api/me/stock-entries/save-products-for-so-event
             */
            Route::post('/save-products-for-so-event', [SelectableSoEventController::class, 'saveProductsForSoEvent'])->name('save_products_for_so_event');

            /**
             * Finalisasi hasil SO Event (POST /api/me/stock-entries/finalize-so-event)
             */
            Route::post('/finalize-so-event', [SelectableSoEventController::class, 'finalizeSoEvent'])->name('api.me.stock_entries.finalize_so_event');
        }); // Tutup group stock-entries

    // Route finalized-so-events harus di luar prefix('me/stock-entries') agar tidak bentrok
    Route::get('/me/stock-entries/finalized-so-events', [SelectableSoEventController::class, 'getFinalizedSoEvents'])->name('api.me.stock_entries.finalized_so_events');

    // ----------------------------------------------------
    // Rute untuk memilih Event Stock Opname oleh Pengguna (dalam grup auth)
    // ----------------------------------------------------
    /**
     * Mengambil daftar Stock Opname Event yang dapat dipilih oleh pengguna.
     * GET /api/me/selectable-so-events
     */
    Route::get('/me/selectable-so-events', [SelectableSoEventController::class, 'index'])->name('api.me.selectable_so_events.index');

    /**
     * Mengambil data stock_audits hari ini untuk user login.
     * GET /api/me/stock-audits/today
     */
    Route::get('/me/stock-audits/today', [SelectableSoEventController::class, 'getTodayStockAudits'])->name('api.me.stock_audits.today');

    // ----------------------------------------------------
    // Rute untuk Pemilihan dan Persiapan Stock Opname Event (dalam grup auth)
    // ----------------------------------------------------
    /**
     * Menyimpan pilihan SO dan menyiapkan sesi entry stok fisik.
     * POST /api/me/select-so-for-entry
     */
    Route::post('/me/select-so-for-entry', [SelectableSoEventController::class, 'selectSoForEntry'])->name('api.me.select_so_for_entry');
});

// ----------------------------------------------------
// Rute untuk Pembaruan Aplikasi Flutter
// ----------------------------------------------------
/**
 * Mengambil informasi versi aplikasi terbaru yang aktif.
 * GET /api/app/latest-version
 */
Route::get('/app/latest-version', [FlutterAppController::class, 'getLatestAppVersion'])->name('api.app.latest_version');

// Endpoint hapus data finalisasi SO Event
Route::delete('/me/stock-entries/finalized-so-events', [SelectableSoEventController::class, 'deleteFinalizedSoEvent'])->name('api.me.stock_entries.delete_finalized_so_event');

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ApiLog;

class ApiControlController extends Controller
{
    /**
     * Daftar endpoint REST API yang dapat dikontrol (dummy/demo).
     */
    private $apiEndpoints = [
        [
            'name' => 'Login',
            'route' => 'login',
            'method' => 'POST',
            'description' => 'Login user (API)',
        ],
        [
            'name' => 'Logout',
            'route' => 'api.logout',
            'method' => 'POST',
            'description' => 'Logout user (API)',
        ],
        [
            'name' => 'User Info',
            'route' => 'api.user',
            'method' => 'GET',
            'description' => 'Ambil data user login',
        ],
        [
            'name' => 'Get Stock Entries',
            'route' => 'api.me.stock-entries.index',
            'method' => 'GET',
            'description' => 'Daftar entri stok user',
        ],
        [
            'name' => 'Update Stock Entry',
            'route' => 'api.me.stock-entries.storeOrUpdate',
            'method' => 'POST',
            'description' => 'Update entri stok user',
        ],
        [
            'name' => 'Pending Stock Entries',
            'route' => 'api.me.stock-entries.pending',
            'method' => 'GET',
            'description' => 'Produk belum diinput user',
        ],
        [
            'name' => 'Stock Entry Filter Options',
            'route' => 'api.me.stock-entries.filterOptions',
            'method' => 'GET',
            'description' => 'Opsi filter stok user',
        ],
        [
            'name' => 'Update Temp Stock Entry',
            'route' => 'api.me.stock-entries.update',
            'method' => 'POST',
            'description' => 'Update stok sementara user',
        ],
        [
            'name' => 'Save Products For SO Event',
            'route' => 'api.me.stock-entries.save_products_for_so_event',
            'method' => 'POST',
            'description' => 'Bulk insert/update stok event',
        ],
        [
            'name' => 'Finalize SO Event',
            'route' => 'api.me.stock_entries.finalize_so_event',
            'method' => 'POST',
            'description' => 'Finalisasi SO Event',
        ],
        [
            'name' => 'Finalized SO Events',
            'route' => 'api.me.stock_entries.finalized_so_events',
            'method' => 'GET',
            'description' => 'Daftar SO Event yang sudah final',
        ],
        [
            'name' => 'Delete Finalized SO Event',
            'route' => 'api.me.stock_entries.delete_finalized_so_event',
            'method' => 'DELETE',
            'description' => 'Hapus data finalisasi SO Event',
        ],
        [
            'name' => 'Selectable SO Events',
            'route' => 'api.me.selectable_so_events.index',
            'method' => 'GET',
            'description' => 'Daftar event SO yang bisa dipilih',
        ],
        [
            'name' => 'Today Stock Audits',
            'route' => 'api.me.stock_audits.today',
            'method' => 'GET',
            'description' => 'Audit stok hari ini',
        ],
        [
            'name' => 'Select SO For Entry',
            'route' => 'api.me.select_so_for_entry',
            'method' => 'POST',
            'description' => 'Pilih event SO untuk entry',
        ],
        [
            'name' => 'Latest App Version',
            'route' => 'api.app.latest_version',
            'method' => 'GET',
            'description' => 'Versi aplikasi Flutter terbaru',
        ],
    ];

    /**
     * Simulasi status aktif/tidaknya endpoint (bisa diganti dengan DB/cache di produksi).
     */
    private function getApiStatus()
    {
        return session('api_status', [0 => true, 1 => true, 2 => true, 3 => true]);
    }
    private function setApiStatus($status)
    {
        session(['api_status' => $status]);
    }

    /**
     * Display the REST API control panel.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Ambil log penggunaan REST API, urutkan terbaru, join user jika ada relasi
        $logs = ApiLog::with('user')->orderByDesc('created_at')->limit(100)->get();

        $endpoints = $this->apiEndpoints;
        $status = $this->getApiStatus();
        return view('admin.api_control.index', [
            'endpoints' => $endpoints,
            'status' => $status,
            'logs' => $logs
        ]);
    }

    public function toggle(Request $request)
    {
        $idx = $request->input('idx');
        $status = $this->getApiStatus();
        if (isset($status[$idx])) {
            $status[$idx] = !$status[$idx];
            $this->setApiStatus($status);
        }
        return redirect()->route('admin.api_control.index');
    }
}

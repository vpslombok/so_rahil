<?php

namespace App\Http\Controllers;

use App\Models\StockAudit;
use App\Models\TempStockEntry; // Tambahkan ini
use Illuminate\Support\Facades\Auth; // Tambahkan ini
use Illuminate\Http\Request;
use App\Models\User; // Tambahkan ini
use Illuminate\Support\Facades\DB; // Tambahkan ini untuk transaksi

class ReportController extends Controller
{
    /**
     * Display a listing of finalized stock opname events, grouped by nomor_nota,
     * optionally filtered by user_id for non-admin users.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string|null $nomor_nota
     * @return \Illuminate\View\View
     */
    public function finalizedStockOpnameReport(Request $request)
    {
        // Mulai membangun query untuk StockAudit
        $query = StockAudit::with(['product', 'stockOpnameEvent', 'user']); // Eager load relasi
        $authUser = Auth::user();

        // Periksa apakah pengguna yang diautentikasi adalah admin
        // Asumsi: Model User memiliki atribut 'role' (misalnya, $authUser->role == 'admin')
        // Sesuaikan 'role' dengan nama kolom peran Anda dan 'admin' dengan nilai peran admin Anda.
        // Jika Anda menggunakan package role-permission, gunakan metode yang sesuai dari package tersebut.
        if ($authUser && $authUser->role === 'admin') {
            // Admin melihat semua data
            // sesuai permintaan "jika role admin maka tampilkan seluruh data"
        } else {
            // Bukan admin atau tidak ada pengguna yang diautentikasi (meskipun middleware 'auth' seharusnya menangani ini)
            if ($request->has('user_id') && $request->filled('user_id')) {
                // Jika user_id spesifik diminta dalam request
                $query->where('user_id', $request->input('user_id'));
            } elseif ($authUser) {
                // Default untuk pengguna non-admin: tampilkan data mereka sendiri jika tidak ada user_id spesifik yang diminta
                $query->where('user_id', $authUser->id);
            }
            // Jika pengguna tidak diautentikasi dan tidak ada user_id di request,
            // query tidak akan memiliki filter user_id (namun ini seharusnya tidak terjadi dalam grup middleware 'auth')
        }

        // Ambil data yang dikelompokkan berdasarkan nomor_nota
        // Kita juga perlu mengambil data lain yang relevan untuk setiap grup, seperti tanggal dan user
        $finalizedGroups = $query
            ->select('nomor_nota', 'stock_opname_event_id', 'user_id', \DB::raw('MAX(checked_at) as latest_checked_at'))
            ->groupBy('nomor_nota', 'stock_opname_event_id', 'user_id')
            ->orderBy('latest_checked_at', 'desc')
            ->paginate(25); // Paginate grup nomor nota

        // Eager load relasi untuk data yang sudah digrup
        $finalizedGroups->load(['stockOpnameEvent', 'user']);

        return view('reports.finalized_stock_opname_summary', compact('finalizedGroups'));
    }

    /**
     * Display the detailed stock audit items for a specific nomor_nota.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string $nomor_nota
     * @return \Illuminate\View\View
     */
    public function showFinalizedStockOpnameDetailsByNota(Request $request, $nomor_nota)
    {
        $authUser = Auth::user();
        $query = StockAudit::with(['product', 'stockOpnameEvent', 'user'])
            ->where('nomor_nota', $nomor_nota);

        // Jika bukan admin, pastikan mereka hanya bisa melihat detail nota yang memang milik mereka
        // atau jika mereka memiliki izin khusus (logika ini bisa disesuaikan)
        if ($authUser && $authUser->role !== 'admin') {
            $query->where('user_id', $authUser->id);
        }

        $stockAuditDetails = $query->orderBy('checked_at', 'desc')->get();

        if ($stockAuditDetails->isEmpty()) {
            abort(404, 'Detail laporan untuk nomor nota ini tidak ditemukan atau Anda tidak memiliki akses.');
        }

        // Hitung total selisih
        $totalDifference = $stockAuditDetails->sum('difference');

        return view('reports.finalized_stock_opname_items_by_nota', compact('stockAuditDetails', 'nomor_nota', 'totalDifference'));
    }

    /**
     * Delete a finalized stock opname group (all entries for a specific user, event, and nomor_nota).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroyFinalizedStockOpnameGroup(Request $request)
    {
        $validated = $request->validate([
            'nomor_nota' => 'required|string',
            'stock_opname_event_id' => 'nullable|integer|exists:stock_opname_events,id',
            'user_id_for_deletion' => 'required|integer|exists:users,id',
        ]);

        $authUser = Auth::user();
        $userIdForDeletion = $validated['user_id_for_deletion'];
        $nomorNota = $validated['nomor_nota'];
        $eventId = $validated['stock_opname_event_id']; // Bisa null

        // Authorization: Admin can delete any, users can only delete their own.
        if ($authUser->role !== 'admin' && $authUser->id != $userIdForDeletion) {
            return redirect()->route('stock_audit_report.summary')
                ->with('error_message', 'Anda tidak memiliki izin untuk menghapus data ini.');
        }

        DB::beginTransaction();
        try {
            // Hapus dari StockAudit
            $stockAuditQuery = StockAudit::where('user_id', $userIdForDeletion)
                                ->where('nomor_nota', $nomorNota);

            if (is_null($eventId)) {
                $stockAuditQuery->whereNull('stock_opname_event_id');
            } else {
                $stockAuditQuery->where('stock_opname_event_id', $eventId);
            }
            $deletedStockAuditCount = $stockAuditQuery->delete();

            // Hapus juga dari TempStockEntry
            $tempStockEntryQuery = TempStockEntry::where('user_id', $userIdForDeletion)
                                    ->where('nomor_nota', $nomorNota);

            if (is_null($eventId)) {
                $tempStockEntryQuery->whereNull('stock_opname_event_id');
            } else {
                $tempStockEntryQuery->where('stock_opname_event_id', $eventId);
            }
            // Tidak masalah jika tidak ada yang terhapus dari temp_stock_entries,
            // karena fokus utama adalah penghapusan dari stock_audits.
            $tempStockEntryQuery->delete();

            DB::commit();

            if ($deletedStockAuditCount > 0) {
                return redirect()->route('stock_audit_report.summary')
                    ->with('success_message', 'Data finalisasi SO (termasuk entri sementara terkait) berhasil dihapus.');
            } else {
                return redirect()->route('stock_audit_report.summary')
                    ->with('error_message', 'Data finalisasi SO tidak ditemukan atau sudah dihapus.');
            }
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Gagal menghapus data finalisasi SO: " . $e->getMessage(), ['nomor_nota' => $nomorNota, 'user_id' => $userIdForDeletion, 'event_id' => $eventId]);
            return redirect()->route('stock_audit_report.summary')
                ->with('error_message', 'Terjadi kesalahan saat menghapus data finalisasi SO.');
        }
    }
}

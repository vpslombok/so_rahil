<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\TempStockEntry;
use App\Models\StockAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SoMonitorController extends Controller
{
    public function index(Request $request): \Illuminate\View\View
    {
        // Query untuk mengambil sesi unik dari TempStockEntry beserta status finalisasinya
        $sessionsQuery = TempStockEntry::select(
            'temp_stock_entries.nomor_nota',
            'temp_stock_entries.user_id',
            'temp_stock_entries.stock_opname_event_id',
            DB::raw('MIN(temp_stock_entries.created_at) as session_start_time'), // Alias tabel untuk created_at
            DB::raw('MAX(temp_stock_entries.updated_at) as last_activity_time'),  // Alias tabel untuk updated_at
            DB::raw("(CASE WHEN EXISTS (
                SELECT 1
                FROM stock_audits sa
                WHERE sa.user_id = temp_stock_entries.user_id
                  AND sa.nomor_nota = temp_stock_entries.nomor_nota
                  AND sa.stock_opname_event_id <=> temp_stock_entries.stock_opname_event_id
            ) THEN 'Finalized' ELSE 'Active' END) as finalization_status")
        )
            ->groupBy(
                'temp_stock_entries.nomor_nota',
                'temp_stock_entries.user_id',
                'temp_stock_entries.stock_opname_event_id'
            );

        // Handle filter pencarian
        if ($request->filled('search_user')) {
            $sessionsQuery->whereHas('user', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->input('search_user') . '%')
                    ->orWhere('username', 'like', '%' . $request->input('search_user') . '%');
            });
        }
        if ($request->filled('search_nota')) {
            $sessionsQuery->where('temp_stock_entries.nomor_nota', 'like', '%' . $request->input('search_nota') . '%');
        }
        if ($request->filled('search_event')) {
            // Filter berdasarkan nama event atau jika event_id null (untuk SO Rak)
            $searchTermEvent = $request->input('search_event');
            $sessionsQuery->where(function ($q) use ($searchTermEvent) {
                $q->whereHas('stockOpnameEvent', function ($sq) use ($searchTermEvent) {
                    $sq->where('name', 'like', '%' . $searchTermEvent . '%');
                })->orWhere(function ($sq) use ($searchTermEvent) {
                    if (stripos('SO Rak Umum', $searchTermEvent) !== false || stripos('Rak Umum', $searchTermEvent) !== false || stripos('Umum', $searchTermEvent) !== false) {
                        $sq->whereNull('temp_stock_entries.stock_opname_event_id');
                    }
                });
            });
        }

        $sessions = $sessionsQuery->with(['user:id,name,username', 'stockOpnameEvent:id,name'])
            ->orderBy('last_activity_time', 'desc')
            ->paginate(20);

        return view('admin.so_monitor.index', compact('sessions'));
    }

    /**
     * Remove the specified active SO session (TempStockEntry records) from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroySession(Request $request)
    {
        $request->validate([
            'nomor_nota' => 'required|string',
            'user_id' => 'required|integer|exists:users,id',
            'stock_opname_event_id' => 'nullable|integer|exists:stock_opname_events,id',
        ]);

        $nomorNota = $request->input('nomor_nota');
        $userId = $request->input('user_id');
        // Jika stock_opname_event_id kosong dari form, akan menjadi null.
        $stockOpnameEventId = $request->input('stock_opname_event_id');

        DB::beginTransaction();
        try {
            $query = TempStockEntry::where('user_id', $userId)
                ->where('nomor_nota', $nomorNota);

            if (!is_null($stockOpnameEventId) && $stockOpnameEventId !== '') {
                $query->where('stock_opname_event_id', $stockOpnameEventId);
            } else {
                $query->whereNull('stock_opname_event_id');
            }

            $deletedCount = $query->delete();
            DB::commit();

            if ($deletedCount > 0) {
                return redirect()->route('admin.so_monitor.index')
                    ->with('success_message', "Sesi SO dengan nota '{$nomorNota}' untuk user ID {$userId} berhasil dihapus ({$deletedCount} entri sementara).");
            } else {
                return redirect()->route('admin.so_monitor.index')
                    ->with('info_message', "Tidak ada entri sementara yang ditemukan atau sudah dihapus untuk sesi SO dengan nota '{$nomorNota}' dan user ID {$userId}.");
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error deleting SO session: Nota {$nomorNota}, UserID {$userId}, EventID {$stockOpnameEventId}. Error: " . $e->getMessage());
            return redirect()->route('admin.so_monitor.index')
                ->with('error_message', 'Gagal menghapus sesi SO: Terjadi kesalahan sistem. Silakan periksa log.');
        }
    }
}

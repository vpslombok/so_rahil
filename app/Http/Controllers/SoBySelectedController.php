<?php

namespace App\Http\Controllers;

use App\Models\StockOpnameEvent;
use App\Models\Rack; // Tambahkan ini
use App\Models\SoSelectedProduct;
use App\Models\TempStockEntry;     // Tambahkan ini
use App\Models\StockAudit;        // Tambahkan ini untuk cek finalisasi
use App\Models\UserProductStock;  // Tambahkan ini
use App\Models\User;              // Tambahkan ini untuk mendapatkan username
use App\Models\Product;           // Tambahkan ini
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator; // Tambahkan ini
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log; // Tambahkan ini

class SoBySelectedController extends Controller
{
    public function index(Request $request)
    {
        // Jika request adalah POST, event_id diambil dari input form.
        // Jika request adalah GET, event_id bisa dari query parameter (misalnya, saat kembali dari halaman lain).
        if ($request->isMethod('post')) {
            $selectedEventId = $request->input('event_id'); // Bisa juga dari GET jika form disubmit dengan GET
        } else {
            $selectedEventId = $request->query('event_id');
        }
        $productsToDisplay = null; // Akan menampung produk yang akan ditampilkan
        $currentEvent = null;
        $unfinalizedEventId = null; // Variabel untuk menyimpan ID event yang belum difinalisasi
        $unfinalizedSoInfo = null; // Variabel untuk menyimpan info SO yang belum difinalisasi (nama event atau info SO Rak)
        $unfinalizedNomorNota = null; // Variabel untuk menyimpan nomor nota SO yang belum difinalisasi
        $userId = Auth::id();
        
        $authUser = Auth::user();
        if (!$authUser) {
            return redirect()->route('login')->with('error_message', 'Sesi tidak valid.');
        }
        $usernamePart = preg_replace('/[^a-zA-Z0-9_]/', '_', strtolower($authUser->username));
        if (empty($usernamePart)) $usernamePart = 'user'; // Fallback

        $todayDatePartForSearch = now()->format('dmy'); // DDMMYY

        // Ambil semua SO Event yang statusnya 'active' untuk pemilihan
        $activeSoEvents = StockOpnameEvent::where('status', 'active')->orderBy('name')->get();

        if ($selectedEventId) {
            $currentEvent = StockOpnameEvent::where('id', $selectedEventId)
                                          ->where('status', 'active') // Pastikan event yang dipilih masih aktif
                                          ->first();
            if ($currentEvent) {
                // Ambil produk yang dipilih untuk SO Event yang dipilih, beserta informasi produk terkait
                $productsQuery = SoSelectedProduct::where('stock_opname_event_id', $selectedEventId)
                                                ->with(['product' => function ($query) {
                                                    $query->with('rack'); // Eager load rack dari produk
                                                }]);

                $productsToDisplay = $productsQuery->paginate(15);

                if ($productsToDisplay->isEmpty() && !$request->has('page')) {
                    $message = 'Tidak ada produk yang ditentukan untuk SO Event "' . $currentEvent->name . '"';
                    return view('so_by_selected.index', compact('activeSoEvents', 'productsToDisplay', 'currentEvent', 'selectedEventId', 'unfinalizedSoInfo', 'unfinalizedNomorNota', 'unfinalizedEventId'))->with('info_message', $message);
                }
            } else {
                // Jika event_id tidak valid atau tidak aktif
                if ($selectedEventId) {
                    return redirect()->route('so_by_selected.index')->with('error_message', 'SO Event tidak valid atau tidak aktif.');
                }
            }
        }

        // Cek apakah ada sesi SO yang belum difinalisasi untuk user ini pada hari ini
        $notaPrefixForToday = $todayDatePartForSearch . '-' . $usernamePart . '-';
        $unfinalizedTempEntry = TempStockEntry::where('user_id', $userId)
                                                ->where('nomor_nota', 'LIKE', $notaPrefixForToday . '%')
                                                ->orderBy('nomor_nota', 'desc') // Ambil yang terbaru
                                                ->first();

        if ($unfinalizedTempEntry) {
            $isFinalizedToday = StockAudit::where('user_id', $userId)
                                          ->where('nomor_nota', $unfinalizedTempEntry->nomor_nota)
                                          // Penting: cocokkan konteks event_id (bisa null)
                                          ->where('stock_opname_event_id', $unfinalizedTempEntry->stock_opname_event_id)
                                          ->exists();

            if (!$isFinalizedToday) {
                // Ditemukan sesi yang belum difinalisasi untuk nomor nota hari ini
                $unfinalizedEventId = $unfinalizedTempEntry->stock_opname_event_id; // Bisa null
                $unfinalizedNomorNota = $unfinalizedTempEntry->nomor_nota;

                if ($unfinalizedEventId) {
                    $event = StockOpnameEvent::find($unfinalizedEventId);
                    $unfinalizedSoInfo = $event ? $event->name : 'Event Tidak Diketahui';
                } else {
                    // Ini adalah SO umum/rak yang belum difinalisasi
                    $unfinalizedSoInfo = 'SO Umum/Rak';
                }
            }
        }

        if ($activeSoEvents->isEmpty() && !$selectedEventId) {
            return view('so_by_selected.index_empty'); // Tampilkan halaman kosong jika tidak ada event aktif sama sekali
        }
        // Kirim unfinalizedEventId juga, agar tombol "Lanjutkan Sesi" bisa mengarahkan ke event yang benar jika ada
        return view('so_by_selected.index', compact('activeSoEvents', 'productsToDisplay', 'currentEvent', 'selectedEventId', 'unfinalizedSoInfo', 'unfinalizedNomorNota', 'unfinalizedEventId'));
    }

    /**
     * Get the next available nomor nota for a given user and date.
     * It checks the stock_audits table for the last finalized sequence.
     * New format: DDMMYY-username-XXX
     */
    private function getNextNomorNota(int $userId): string
    {
        $user = User::find($userId);
        if (!$user) {
            Log::error("User not found with ID: {$userId} in getNextNomorNota.");
            throw new \Exception("User not found for ID: {$userId}");
        }
        // Sanitize username: replace non-alphanumeric with underscore, convert to lowercase
        $usernamePart = preg_replace('/[^a-zA-Z0-9_]/', '_', strtolower($user->username));
        if (empty($usernamePart)) {
            $usernamePart = 'user'; // Fallback if username becomes empty after sanitization
        }

        $todayDatePart = now()->format('dmy'); // DDMMYY

        $prefixForNota = $todayDatePart . '-' . $usernamePart . '-';

        $lastFinalizedNota = StockAudit::where('user_id', $userId)
            ->where('nomor_nota', 'LIKE', $prefixForNota . '%')
            ->orderBy('nomor_nota', 'desc')
            ->value('nomor_nota');

        $lastSequence = 0;
        if ($lastFinalizedNota) {
            if (strpos($lastFinalizedNota, $prefixForNota) === 0) {
                $sequenceStr = substr($lastFinalizedNota, strlen($prefixForNota));
                if (is_numeric($sequenceStr)) {
                    $lastSequence = (int)$sequenceStr;
                } else {
                    Log::warning("Could not parse numeric sequence from '{$sequenceStr}' in nomor_nota '{$lastFinalizedNota}'. Prefix: '{$prefixForNota}'.");
                }
            } else {
                Log::warning("Last finalized nota '{$lastFinalizedNota}' does not match expected prefix '{$prefixForNota}'.");
            }
        }

        $nextSequence = $lastSequence + 1;
        return $prefixForNota . str_pad($nextSequence, 3, '0', STR_PAD_LEFT);
    }

    public function prepareForEntry(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'event_id' => 'required|exists:stock_opname_events,id',
        ]);

        if ($validator->fails()) {
            return redirect()->route('so_by_selected.index', ['event_id' => $request->input('event_id')])
                        ->withErrors($validator)
                        ->withInput()
                        ->with('error_message', 'Input tidak valid.');
        }

        $eventId = $request->input('event_id');
        if (!$eventId) { // Seharusnya sudah ditangani validator 'required'
            return redirect()->route('so_by_selected.index')
                ->with('error_message', 'Pilih SO Event untuk melanjutkan persiapan entri.');
        }

        $userId = Auth::id();
        if (!$userId) { return redirect()->route('login')->with('error_message', 'Sesi Anda telah berakhir. Silakan login kembali.');}

        $authUser = Auth::user(); // Mengambil model User yang terautentikasi
        if (!$authUser) { // Pengaman tambahan
            return redirect()->route('login')->with('error_message', 'Sesi tidak valid.');
        }
        $usernamePart = preg_replace('/[^a-zA-Z0-9_]/', '_', strtolower($authUser->username));
        if (empty($usernamePart)) $usernamePart = 'user';

        $todayDatePartForSearch = now()->format('dmy'); // DDMMYY
        $notaPrefixForToday = $todayDatePartForSearch . '-' . $usernamePart . '-';
        $targetNomorNota = '';

        // Cek apakah ada sesi TempStockEntry yang aktif (belum difinalisasi) untuk user ini hari ini
        $anyActiveSession = TempStockEntry::where('user_id', $userId)
            ->where('nomor_nota', 'LIKE', $notaPrefixForToday . '%')
            ->orderBy('nomor_nota', 'desc')
            ->first();

        if ($anyActiveSession) {
            $isFinalized = StockAudit::where('user_id', $userId)
                ->where('nomor_nota', $anyActiveSession->nomor_nota)
                ->where('stock_opname_event_id', $anyActiveSession->stock_opname_event_id) // Cocokkan konteks event_id (bisa null)
                ->exists();

            if (!$isFinalized) {
                // Ada sesi yang belum difinalisasi. Cek apakah ini sesi yang sama dengan yang diminta.
                // $eventId bisa null (untuk SO rak). $anyActiveSession->stock_opname_event_id juga bisa null.
                $isSameSOContext = ($anyActiveSession->stock_opname_event_id == $eventId);

                if ($isSameSOContext) {
                    // Lanjutkan sesi yang ada
                    $targetNomorNota = $anyActiveSession->nomor_nota;
                    $logMessage = $eventId ? "event_id: {$eventId}" : "SO Rak";
                    Log::info("SoBySelectedController@prepareForEntry: Melanjutkan sesi untuk user_id: {$userId}, {$logMessage} dengan nomor_nota: {$targetNomorNota}.");

                    $redirectParams = $eventId ? ['stock_opname_event' => $eventId] : [];
                    return redirect()->route('stock_check.index', $redirectParams)
                                   ->with('info_message', 'Melanjutkan sesi entri stok yang sudah ada (Nota: ' . $targetNomorNota . ').');
                } else {
                    // Ada SO lain yang belum selesai hari ini, blok pengguna.
                    $conflictingEventId = $anyActiveSession->stock_opname_event_id;
                    $conflictingSoName = 'SO Umum/Rak';
                    if ($conflictingEventId) {
                        $eventModel = StockOpnameEvent::find($conflictingEventId);
                        $conflictingSoName = $eventModel ? $eventModel->name : "ID Event: {$conflictingEventId}";
                    }
                    Log::warning("SoBySelectedController@prepareForEntry: User_id: {$userId} mencoba memulai SO baru (Event: {$eventId}), tetapi ada SO '{$conflictingSoName}' (Nota: {$anyActiveSession->nomor_nota}) yang belum difinalisasi.");
                    
                    $redirectParams = [];
                    if ($conflictingEventId) {
                        $redirectParams['event_id'] = $conflictingEventId;
                    } else {
                        // If conflicting SO is rack-only, we might not have its specific rack_id here easily.
                        // or try to find the rack if it's a pure rack SO. For simplicity, general redirect.
                        unset($redirectParams['rack_id']); // Avoid incorrect rack_id if conflicting is general
                        if ($anyActiveSession->stock_opname_event_id === null) {
                             // Attempt to find a rack if it was a rack-only SO, though this is complex
                             // as TempStockEntry doesn't directly store rack_id for the whole session.
                        }
                    }

                    return redirect()->route('so_by_selected.index', $redirectParams)
                                   ->with('error_message', "Selesaikan SO '{$conflictingSoName}' (Nota: {$anyActiveSession->nomor_nota}) yang belum difinalisasi sebelum memulai SO baru hari ini.");
                }
            }
        }

        // Jika tidak ada sesi aktif yang belum difinalisasi, atau sesi yang ada sudah difinalisasi, buat nomor nota baru.
        $targetNomorNota = $this->getNextNomorNota($userId);

        $productsToProcess = collect();
        $event = null; // Untuk menyimpan model event jika eventId ada

        if ($eventId) {
            $event = StockOpnameEvent::find($eventId);
            if (!$event || !in_array($event->status, ['active', 'counted'])) { // Memungkinkan 'counted' juga
                return redirect()->route('so_by_selected.index', ['event_id' => $eventId])
                            ->with('error_message', 'SO Event tidak valid atau tidak aktif.');
            }

            $selectedProductsQuery = SoSelectedProduct::where('stock_opname_event_id', $eventId)->with('product');
            $productsToProcess = $selectedProductsQuery->get()->map(fn($ssp) => $ssp->product)->filter();

        } // else case ($eventId null) sudah ditangani validator 'required'

        if ($productsToProcess->isEmpty()) {
            $logMessage = "Tidak ada produk ditemukan untuk ";
            $message = "Tidak ada produk yang ditemukan ";
            if ($eventId && $event) {
                $logMessage .= "event ID {$eventId} ('{$event->name}')";
                $message .= "untuk SO Event '{$event->name}'";
            }
            Log::info("SoBySelectedController@prepareForEntry: " . $logMessage);
            return redirect()->route('so_by_selected.index', ['event_id' => $eventId])
                       ->with('info_message', $message . ' Tidak dapat mempersiapkan entri.');
        }

        DB::beginTransaction();
        try {
            // Hapus semua entri sementara sebelumnya untuk user ini dengan nomor_nota target.
            // Ini penting untuk memastikan kita memulai dengan bersih untuk nomor_nota ini.
            Log::info("SoBySelectedController@prepareForEntry: Menghapus TempStockEntry untuk user_id: {$userId}, nomor_nota: {$targetNomorNota} sebelum membuat yang baru.");
            TempStockEntry::where('user_id', $userId)
                          ->where('nomor_nota', $targetNomorNota)
                          ->delete();

            $logContext = "Event ID {$eventId}";
            Log::info("SoBySelectedController@prepareForEntry: Menggunakan Nomor Nota: {$targetNomorNota} untuk {$logContext}, User ID {$userId}");

            foreach ($productsToProcess as $product) {
                if (!$product) {
                    // Ini seharusnya tidak terjadi jika $productsToProcess difilter dengan benar
                    Log::warning("SoBySelectedController@prepareForEntry: Ditemukan produk null dalam daftar proses untuk {$logContext}, User ID {$userId}.");
                    continue; // Lewati jika produk tidak ada (seharusnya tidak terjadi dengan foreign key yang benar)
                }

                // Ambil stok sistem untuk user dan produk saat ini
                $userProductStock = UserProductStock::where('user_id', $userId)
                                                  ->where('product_id', $product->id)
                                                  ->first();
                $systemStock = $userProductStock ? $userProductStock->stock : 0;

                $tempEntryData = [
                    'user_id' => $userId,
                    'stock_opname_event_id' => $eventId, // Akan null jika SO berdasarkan rak saja
                    'nomor_nota' => $targetNomorNota, // Gunakan nomor_nota yang sudah ditentukan
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'barcode' => $product->barcode,
                    'product_code' => $product->product_code,
                    'system_stock' => $systemStock,
                    'physical_stock' => null, // Akan diisi pada tahap entri stok fisik
                    'difference' => null,     // Akan dihitung setelah stok fisik diisi
                ];
                // Log::debug("SoBySelectedController@prepareForEntry: Membuat TempStockEntry dengan data:", $tempEntryData); // Ganti ke debug jika terlalu verbose
                TempStockEntry::create($tempEntryData);
            }
            DB::commit();
            Log::info("SoBySelectedController@prepareForEntry: Transaksi berhasil di-commit untuk {$logContext}, Nomor Nota: {$targetNomorNota}, User ID {$userId}");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error preparing temp stock entry for {$logContext}, User ID {$userId}: " . $e->getMessage() . "\n" . $e->getTraceAsString()); // Removed rack_id from redirect
            return redirect()->route('so_by_selected.index', ['event_id' => $eventId])
                        ->with('error_message', 'Gagal mempersiapkan data untuk entri: Terjadi kesalahan sistem.');
        }

        // Redirect ke halaman stock_check.index.
        // Jika eventId null, parameter 'stock_opname_event' tidak akan dikirim.
        // StockCheckController@index harus bisa menangani ini (misalnya, dengan mencari TempStockEntry berdasarkan user_id dan nomor_nota hari ini jika event tidak ada di URL).
        $redirectParams = [];
        if ($eventId) {
            $redirectParams['stock_opname_event'] = $eventId;
        }
        return redirect()->route('stock_check.index', $redirectParams)
                       ->with('success_message', 'Data SO telah disiapkan. Silakan mulai entri stok fisik.');
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StockOpnameEvent; // Menggunakan StockOpnameEvent agar konsisten
use App\Models\TempStockEntry;  // Menggunakan TempStockEntry yang disiapkan sebelumnya
use App\Models\User;            // Ditambahkan untuk mendapatkan username
use App\Models\StockAudit;      // Diasumsikan model ini ada untuk cek finalisasi
use App\Models\Product; // Jika Anda perlu mengakses model Product
use App\Models\UserProductStock; // Tambahkan ini untuk mengambil stok terbaru
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth; // Ditambahkan untuk Auth::id()
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator; // Untuk validasi manual jika diperlukan

class StockCheckController extends Controller
{
    /**
     * Display the stock checking page.
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\StockOpnameEvent|null  $stock_opname_event
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, StockOpnameEvent $stock_opname_event_from_url = null)
    {
        $currentEvent = null;
        $isRackOnlySoActive = false; // Flag untuk menandakan SO berbasis rak yang aktif
        $userId = auth()->id();
        $displayNomorNota = null;

        if (!$userId) {
            return redirect()->route('login')->with('error_message', 'Sesi Anda telah berakhir. Silakan login kembali.');
        }

        $authUser = User::find($userId); // Mengambil model User
        if (!$authUser) { // Pengaman tambahan
            return redirect()->route('login')->with('error_message', 'User tidak ditemukan.');
        }
        $usernamePart = preg_replace('/[^a-zA-Z0-9_]/', '_', strtolower($authUser->username));
        if (empty($usernamePart)) $usernamePart = 'user'; // Fallback
        $todayDatePartForComparison = now()->format('dmy'); // DDMMYY format for today's date part

        // 1. Handle event yang secara eksplisit dilewatkan melalui URL
        if ($stock_opname_event_from_url && $stock_opname_event_from_url->exists) {
            $potentialEvent = $stock_opname_event_from_url;
            $tempEntryForUrlEvent = TempStockEntry::where('user_id', $userId)
                ->where('stock_opname_event_id', $potentialEvent->id)
                ->orderBy('created_at', 'desc') // Ambil yang terbaru jika ada beberapa (seharusnya tidak untuk sesi aktif)
                ->first();

            if ($tempEntryForUrlEvent) {
                $nomorNotaFromEntry = $tempEntryForUrlEvent->nomor_nota;
                $partsFromNota = explode('-', $nomorNotaFromEntry);

                if (count($partsFromNota) === 3) {
                    $dateFromNota = $partsFromNota[0]; // DDMMYY
                    $usernameFromNotaInNota = $partsFromNota[1];

                    // Opsional: Validasi username dalam nota jika perlu (misalnya, jika admin melihat nota user lain)
                    // Untuk saat ini, kita fokus pada tanggal.

                    if ($dateFromNota !== $todayDatePartForComparison) {
                        // Nomor nota berasal dari tanggal sebelumnya
                        $isFinalized = StockAudit::where('user_id', $userId)
                            ->where('nomor_nota', $nomorNotaFromEntry)
                            ->where('stock_opname_event_id', $potentialEvent->id)
                            ->exists();

                        if (!$isFinalized) {
                            Log::warning("User {$userId} mencoba mengakses event {$potentialEvent->id} ('{$potentialEvent->name}') dengan nomor_nota {$nomorNotaFromEntry} dari tanggal sebelumnya yang belum difinalisasi.");
                            return redirect()->route('so_by_selected.index', ['event_id' => $potentialEvent->id])
                                ->with('error_message', "Nomor nota '{$nomorNotaFromEntry}' untuk SO Event '{$potentialEvent->name}' berasal dari tanggal sebelumnya dan belum difinalisasi. Untuk melanjutkan event ini dengan nomor nota hari ini, silakan kembali dan pilih 'Persiapkan untuk Entri' untuk SO Event '{$potentialEvent->name}'.");
                        } else {
                            // Nomor nota lama, tapi sudah difinalisasi. Arahkan ke laporan atau halaman pemilihan.
                            Log::info("User {$userId} mencoba mengakses event {$potentialEvent->id} ('{$potentialEvent->name}') dengan nomor_nota {$nomorNotaFromEntry} dari tanggal sebelumnya yang sudah difinalisasi.");
                            return redirect()->route('stock_audit_report.index', ['event_id' => $potentialEvent->id]) // Asumsi ada route ini
                                ->with('info_message', "Sesi entri dengan nomor nota '{$nomorNotaFromEntry}' untuk SO Event '{$potentialEvent->name}' sudah difinalisasi. Anda dapat melihat laporannya di sini atau memilih SO Event lain.");
                        }
                    } else {
                        // Nomor nota adalah untuk hari ini, cek status event
                        if (in_array($potentialEvent->status, ['active', 'counted'])) {
                            $currentEvent = $potentialEvent;
                            $displayNomorNota = $nomorNotaFromEntry;
                        }
                        // Jika status tidak 'active' atau 'counted', $currentEvent akan tetap null dan ditangani nanti
                    }
                } else {
                    Log::error("Malformed nomor_nota '{$nomorNotaFromEntry}' encountered for user {$userId}, event {$potentialEvent->id}.");
                    return redirect()->route('so_by_selected.index', ['event_id' => $potentialEvent->id])
                        ->with('error_message', "Format nomor nota '{$nomorNotaFromEntry}' tidak valid.");
                }
            }
            // Jika tidak ada $tempEntryForUrlEvent, $currentEvent tetap null, akan ditangani di bawah.
        }

        // 2. Jika tidak ada event valid dari URL (atau event dari URL tidak valid), cari sesi aktif terakhir yang belum difinalisasi (TIDAK HANYA HARI INI)
        if (!$currentEvent) {
            // Cari SEMUA temp entry untuk user ini, urutkan dari yang terbaru
            $activeTempEntries = TempStockEntry::where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->get();

            foreach ($activeTempEntries as $activeTempEntry) {
                $isFinalized = StockAudit::where('user_id', $userId)
                    ->where('nomor_nota', $activeTempEntry->nomor_nota)
                    ->where('stock_opname_event_id', $activeTempEntry->stock_opname_event_id)
                    ->exists();

                if (!$isFinalized) {
                    if ($activeTempEntry->stock_opname_event_id !== null) {
                        $eventCandidate = StockOpnameEvent::find($activeTempEntry->stock_opname_event_id);
                        if ($eventCandidate && in_array($eventCandidate->status, ['active', 'counted'])) {
                            $currentEvent = $eventCandidate;
                            $displayNomorNota = $activeTempEntry->nomor_nota;
                            Log::info("StockCheckController@index: Melanjutkan sesi aktif (boleh lintas hari) untuk user {$userId}, event {$currentEvent->id} ('{$currentEvent->name}'), nomor_nota {$displayNomorNota}.");
                            break;
                        }
                    } else {
                        // Ini adalah SO berbasis rak yang belum difinalisasi
                        $isRackOnlySoActive = true;
                        $displayNomorNota = $activeTempEntry->nomor_nota;
                        Log::info("StockCheckController@index: Melanjutkan sesi aktif SO Rak (boleh lintas hari) untuk user {$userId}, nomor_nota {$displayNomorNota}.");
                        break;
                    }
                }
            }
        }

        // 3. Validasi akhir dan pengambilan data
        if (!$currentEvent && !$isRackOnlySoActive) {
            // Tidak ada event dari URL yang valid, dan tidak ada sesi aktif (event maupun rak) yang ditemukan.
            $message = 'Silakan pilih SO Event atau Rak terlebih dahulu, atau persiapkan entri baru jika sesi sebelumnya tidak valid/tidak ditemukan.';
            if ($stock_opname_event_from_url && $stock_opname_event_from_url->exists) {
                // Jika event ada di URL tapi gagal validasi sebelumnya (misal status tidak aktif, sudah final, beda tanggal)
                $message = "Sesi untuk SO Event '{$stock_opname_event_from_url->name}' tidak dapat dilanjutkan atau tidak valid. Silakan persiapkan entri baru atau pilih SO yang lain.";
            }
            return redirect()->route('so_by_selected.index')->with('status', $message);
        }

        // Validasi status event hanya jika ini adalah SO berbasis event
        if ($currentEvent && !in_array($currentEvent->status, ['active', 'counted'])) {
            return redirect()->route('so_by_selected.index')
                ->with('error_message', "SO Event '{$currentEvent->name}' tidak lagi aktif atau statusnya tidak memungkinkan untuk entri.");
        }

        // Ambil entri stok sementara yang telah disiapkan untuk user dan SO Event ini
        // Data ini berasal dari tabel 'temp_stock_entries'
        $productsQuery = TempStockEntry::where('user_id', $userId)
            ->where('nomor_nota', $displayNomorNota); // $displayNomorNota seharusnya sudah terisi jika lolos validasi

        if ($currentEvent) {
            $productsQuery->where('stock_opname_event_id', $currentEvent->id);
        } elseif ($isRackOnlySoActive) {
            $productsQuery->whereNull('stock_opname_event_id');
        } else {
            // Kondisi ini seharusnya tidak tercapai jika validasi di atas benar
            Log::error("StockCheckController@index: Konteks SO tidak jelas saat mengambil produk. UserID: {$userId}, Event: " . ($currentEvent ? $currentEvent->id : 'N/A') . ", RackSO: {$isRackOnlySoActive}, Nota: {$displayNomorNota}");
            return redirect()->route('so_by_selected.index')->with('error_message', 'Gagal memuat data entri stok: Konteks SO tidak jelas.');
        }

        $productsForEntry = $productsQuery->with('product')->orderBy('id')->paginate(50);

        // Jika $displayNomorNota belum terisi atau tidak ada produk (seharusnya sudah ditangani)
        if (!$displayNomorNota && $productsForEntry->isEmpty()) {
            Log::warning("StockCheckController@index: Tidak ada produk untuk entri atau nomor nota tidak diset setelah query. UserID: {$userId}, Event: " . ($currentEvent ? $currentEvent->id : 'N/A') . ", RackSO: {$isRackOnlySoActive}, Nota: {$displayNomorNota}");
            $message = $currentEvent ? "Tidak ada produk yang disiapkan untuk SO Event '{$currentEvent->name}'." : "Tidak ada produk yang disiapkan untuk SO Rak ini.";
            if ($displayNomorNota) $message .= " (Nota: {$displayNomorNota})";
            return redirect()->route('so_by_selected.index')->with('info_message', $message . ' Silakan persiapkan entri kembali.');
        }

        // Fallback jika nomor nota belum terambil tapi ada produk (seharusnya sudah ter-cover di atas)
        if (!$displayNomorNota && isset($productsForEntry) && !$productsForEntry->isEmpty()) {
            $displayNomorNota = $productsForEntry->first()->nomor_nota;
        }

        // Pastikan $displayNomorNota terisi sebelum ke view
        if (!$displayNomorNota) {
            Log::error("StockCheckController@index: displayNomorNota masih null sebelum render view. UserID: {$userId}, Event: " . ($currentEvent ? $currentEvent->id : 'N/A') . ", RackSO: {$isRackOnlySoActive}");
            return redirect()->route('so_by_selected.index')->with('error_message', 'Gagal memuat sesi entri: Nomor nota tidak ditemukan.');
        }

        return view('stock_check.index', [
            'currentEvent' => $currentEvent, // Bisa null jika SO berbasis rak
            'isRackOnlySo' => $isRackOnlySoActive, // Kirim flag ini ke view
            'productsForEntry' => $productsForEntry,
            'displayNomorNota' => $displayNomorNota, // Kirim nomor nota ke view
        ]);
    }

    /**
     * Store the recorded stock data.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'so_event_id' => 'nullable|exists:stock_opname_events,id', // Event ID is now optional
            'nomor_nota' => 'required|string', // Nomor nota is crucial for identifying the session
            'products' => 'required|array',
            // Validasi akan merujuk ke ID dari TempStockEntry
            'products.*.temp_stock_entry_id' => 'required|exists:temp_stock_entries,id',
            'products.*.recorded_stock' => 'required|integer|min:0',
        ]);

        $soEventId = $request->input('so_event_id'); // Can be null
        $nomorNota = $request->input('nomor_nota');
        // Ambil halaman saat ini dari request, default ke 1 jika tidak ada.
        // Ini penting untuk redirect kembali ke halaman yang sama jika terjadi error.
        $currentPage = $request->input('page', 1);

        // We don't strictly need $currentStockOpnameEvent model here if $soEventId is null.
        // The context (event or rack-only) is within each TempStockEntry.
        if ($validator->fails()) {
            return redirect()->route('stock_check.index', ['stock_opname_event' => $soEventId, 'page' => $currentPage])
                ->withErrors($validator)
                ->withInput()
                ->with('error_message', 'Terdapat kesalahan pada input. Silakan periksa kembali.');
        }

        // If soEventId is provided, ensure the event exists (validator already does this, but an extra check is fine)
        if ($soEventId) {
            $currentStockOpnameEvent = StockOpnameEvent::find($soEventId);
            if (!$currentStockOpnameEvent) {
                Log::error("StockCheckController@store: StockOpnameEvent dengan ID {$soEventId} tidak ditemukan meskipun lolos validasi 'exists'.");
                return redirect()->route('so_by_selected.index')->with('error_message', 'SO Event tidak valid.');
            }
        }

        $productsData = $request->input('products');
        DB::beginTransaction();
        try {
            foreach ($productsData as $itemSoEventProductId => $data) {
                // $itemKey adalah key dari array products, yang biasanya adalah ID TempStockEntry jika form di-setup demikian.
                // Namun, kita akan mengandalkan 'temp_stock_entry_id' dari data yang disubmit.
                $recordedStock = $data['recorded_stock'];
                $tempStockEntryIdFromInput = $data['temp_stock_entry_id'];

                $tempEntry = TempStockEntry::find($tempStockEntryIdFromInput);

                if (
                    $tempEntry &&
                    $tempEntry->user_id == auth()->id() &&
                    $tempEntry->nomor_nota == $nomorNota && // Crucial: check nomor_nota
                    $tempEntry->stock_opname_event_id == $soEventId // This ensures consistency if event_id is provided
                ) {
                    $currentUserProductStock = UserProductStock::where('user_id', auth()->id())
                        ->where('product_id', $tempEntry->product_id)
                        ->first();

                    // Use the latest system stock, default to 0 if not found (though it should exist if tempEntry was created correctly)
                    $currentSystemStock = $currentUserProductStock ? $currentUserProductStock->stock : 0;

                    $tempEntry->physical_stock = $recordedStock;
                    // Update system_stock in temp entry with the latest value
                    $tempEntry->system_stock = $currentSystemStock;
                    // Calculate difference based on the latest system stock
                    $tempEntry->difference = $recordedStock - $currentSystemStock;
                    $tempEntry->save();
                } else {
                    $actualNomorNota = $tempEntry ? $tempEntry->nomor_nota : 'N/A';
                    $actualEventIdInTemp = $tempEntry ? ($tempEntry->stock_opname_event_id ?? 'NULL') : 'N/A';
                    $expectedEventId = $soEventId ?? 'NULL';

                    Log::warning("StockCheckController@store: TempStockEntry tidak cocok atau tidak ditemukan (ID: {$tempStockEntryIdFromInput}) untuk User ID " . auth()->id() . ". Expected Nomor Nota: {$nomorNota}, Actual: {$actualNomorNota}. Expected SO Event ID: {$expectedEventId}, Actual: {$actualEventIdInTemp}.");
                    throw new \Exception("Entri stok dengan ID {$tempStockEntryIdFromInput} tidak valid untuk sesi ini.");
                }
            }

            DB::commit();

            // Redirect to show_differences using nomor_nota.
            // If an event_id was part of this SO, it will be implicitly handled by showDifferences
            // when it loads data based on nomor_nota. The stock_opname_event parameter is no longer needed for this route.
            $redirectParamsForDifferences = [
                'nomor_nota' => $nomorNota
            ];

            return redirect()->route('stock_check.show_differences', $redirectParamsForDifferences)
                ->with('success_message', 'Data stok fisik berhasil disimpan.');
        } catch (\Exception $e) {
            DB::rollBack();
            $logContext = $soEventId ? "SO Event ID {$soEventId}" : "SO Rak";
            Log::error("Error saving stock check data for {$logContext}, Nomor Nota {$nomorNota}: " . $e->getMessage() . "\n" . $e->getTraceAsString());

            $redirectParamsForIndex = ['page' => $currentPage];
            if ($soEventId) $redirectParamsForIndex['stock_opname_event'] = $soEventId;
            return redirect()->route('stock_check.index', $redirectParamsForIndex)
                ->withInput()
                ->with('error_message', 'Terjadi kesalahan saat menyimpan data: ' . $e->getMessage());
        }
    }

    /**
     * Display the differences for a given stock opname event.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string $nomor_nota The nomor_nota for the SO session
     * @return \Illuminate\Http\Response
     */
    public function showDifferences(Request $request, string $nomor_nota)
    {
        $userId = auth()->id();
        if (!$userId) {
            return redirect()->route('login')->with('error_message', 'Sesi Anda telah berakhir. Silakan login kembali.');
        }

        // Fetch context entry ONCE
        $contextEntry = TempStockEntry::where('user_id', $userId)
            ->where('nomor_nota', $nomor_nota)
            ->select('stock_opname_event_id')
            ->first();

        if (!$contextEntry) {
            \Log::warning("StockCheckController@showDifferences: No TempStockEntry found for user_id {$userId} and nomor_nota {$nomor_nota}.");
            return redirect()->route('so_by_selected.index')
                ->with('error_message', 'Sesi Stock Opname dengan nota tersebut tidak ditemukan atau belum ada entri.');
        }

        $stock_opname_event = $contextEntry->stock_opname_event_id
            ? StockOpnameEvent::find($contextEntry->stock_opname_event_id)
            : null;

        // Ambil semua temp entries sekaligus, eager load product, dan ambil system_stock terbaru dari user_product_stock
        $tempEntries = TempStockEntry::where('user_id', $userId)
            ->where('nomor_nota', $nomor_nota)
            ->whereNotNull('physical_stock')
            ->with(['product:id,name,product_code,barcode'])
            ->orderBy('product_name')
            ->get();

        // Ambil semua product_id
        $productIds = $tempEntries->pluck('product_id')->unique()->toArray();
        // Ambil stok terbaru dari user_product_stock sekaligus
        $userProductStocks = \App\Models\UserProductStock::where('user_id', $userId)
            ->whereIn('product_id', $productIds)
            ->pluck('stock', 'product_id');

        // Hitung difference secara real-time dan filter hanya yang difference != 0
        $differences = $tempEntries->map(function ($item) use ($userProductStocks) {
            $systemStock = $userProductStocks[$item->product_id] ?? 0;
            $item->system_stock = $systemStock;
            $item->difference = $item->physical_stock - $systemStock;
            return $item;
        })->filter(function ($item) {
            return $item->difference != 0;
        });

        // Paginate secara manual jika perlu (karena sudah collection)
        $perPage = 50;
        $currentPage = \Illuminate\Pagination\Paginator::resolveCurrentPage() ?: 1;
        $pagedDifferences = new \Illuminate\Pagination\LengthAwarePaginator(
            $differences->forPage($currentPage, $perPage),
            $differences->count(),
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return view('stock_check.show_differences', [
            'stock_opname_event' => $stock_opname_event,
            'differences' => $pagedDifferences,
            'nomorNota' => $nomor_nota
        ]);
    }

    /**
     * Find a product within a specific SO event and nomor nota for quick entry.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function findProductForEntry(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'barcode_or_code' => 'required|string',
            'so_event_id' => 'nullable|exists:stock_opname_events,id', // Event ID is optional
            'nomor_nota' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Input tidak valid.', 'errors' => $validator->errors()], 400);
        }

        $userId = auth()->id();
        $barcodeOrCode = $request->input('barcode_or_code');
        $soEventId = $request->input('so_event_id'); // Can be null
        $nomorNota = $request->input('nomor_nota');

        $tempEntry = TempStockEntry::where('user_id', $userId)
            ->where('stock_opname_event_id', $soEventId) // This will correctly match NULL if $soEventId is NULL
            ->where('nomor_nota', $nomorNota)
            ->whereHas('product', function ($query) use ($barcodeOrCode) {
                $query->where('barcode', $barcodeOrCode)
                    ->orWhere('product_code', $barcodeOrCode);
            })
            ->with('product:id,name,product_code,barcode') // Hanya ambil kolom yang dibutuhkan dari produk
            ->select('id as temp_stock_entry_id', 'product_id', 'system_stock', 'physical_stock') // Ambil ID TempStockEntry
            ->first();

        if ($tempEntry && $tempEntry->product) {
            return response()->json([
                'success' => true,
                'product' => [
                    'temp_stock_entry_id' => $tempEntry->temp_stock_entry_id,
                    'name' => $tempEntry->product->name,
                    'product_code' => $tempEntry->product->product_code,
                    'barcode' => $tempEntry->product->barcode,
                    'system_stock' => $tempEntry->system_stock,
                    'physical_stock' => $tempEntry->physical_stock,
                ]
            ]);
        }

        $contextMessage = $soEventId ? "SO Event ini" : "SO Rak ini";
        return response()->json(['success' => false, 'message' => "Produk tidak ditemukan dalam {$contextMessage} atau untuk nomor nota ini."], 404);
    }

    /**
     * Update a single stock entry via AJAX (typically from a modal).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateSingleStock(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'temp_stock_entry_id' => 'required|exists:temp_stock_entries,id',
            'recorded_stock' => 'required|integer|min:0', // Physical stock
            // 'so_event_id' and 'nomor_nota' are not strictly needed here if temp_stock_entry_id is unique and user-scoped.
            // However, for robustness, we can check against the tempEntry's own nomor_nota and so_event_id.
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Data tidak valid.', 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $tempEntry = TempStockEntry::find($request->input('temp_stock_entry_id'));
            $userId = auth()->id();

            // Validate ownership and context (nomor_nota from tempEntry should match an active session for the user)
            if (!$tempEntry || $tempEntry->user_id != $userId) {
                Log::warning("StockCheckController@updateSingleStock: Attempt to update TempStockEntry ID {$request->input('temp_stock_entry_id')} by unauthorized user {$userId} or entry not found.");
                throw new \Exception('Entri stok tidak valid atau Anda tidak berhak mengubahnya.');
            }

            $currentUserProductStock = UserProductStock::where('user_id', auth()->id())
                ->where('product_id', $tempEntry->product_id)
                ->first();
            $currentSystemStock = $currentUserProductStock ? $currentUserProductStock->stock : 0;

            $tempEntry->physical_stock = $request->input('recorded_stock');
            $tempEntry->system_stock = $currentSystemStock;
            $tempEntry->difference = $tempEntry->physical_stock - $currentSystemStock;
            $tempEntry->save();

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Stok fisik berhasil diperbarui.', 'data' => ['temp_stock_entry_id' => $tempEntry->id, 'physical_stock' => $tempEntry->physical_stock]]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error updating single stock entry: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Reset a single stock entry's physical_stock to null.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resetSingleStock(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'temp_stock_entry_id' => 'required|exists:temp_stock_entries,id',
            // 'so_event_id' and 'nomor_nota' not strictly needed if temp_stock_entry_id is the key
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Data tidak valid.', 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $tempEntry = TempStockEntry::find($request->input('temp_stock_entry_id'));
            $userId = auth()->id();

            // Validate ownership
            if (!$tempEntry || $tempEntry->user_id != $userId) {
                Log::warning("StockCheckController@resetSingleStock: Attempt to reset TempStockEntry ID {$request->input('temp_stock_entry_id')} by unauthorized user {$userId} or entry not found.");
                throw new \Exception('Entri stok tidak valid atau Anda tidak berhak mengubahnya.');
            }

            $tempEntry->physical_stock = null; // Reset stok fisik menjadi null
            $tempEntry->difference = null;     // Reset selisih juga menjadi null
            // system_stock di temp_entry biarkan seperti saat SO disiapkan atau terakhir diupdate.
            $tempEntry->save();

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Input stok fisik berhasil direset.',
                'data' => ['temp_stock_entry_id' => $tempEntry->id, 'physical_stock' => $tempEntry->physical_stock] // akan null
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error resetting single stock entry (ID: {$request->input('temp_stock_entry_id')}): " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal mereset stok: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Finalize the stock opname for a specific event and nomor nota.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $nomor_nota
     * @return \Illuminate\Http\Response
     */
    public function finalize(Request $request, string $nomor_nota)
    {
        $userId = auth()->id();

        // Fetch one temp entry to get context (event_id if any) for this nomor_nota and user
        $contextEntry = TempStockEntry::where('user_id', $userId)
            ->where('nomor_nota', $nomor_nota)
            ->select('stock_opname_event_id') // We only need event_id for context
            ->first();

        if (!$contextEntry) {
            Log::warning("StockCheckController@finalize: No TempStockEntry found for user_id {$userId} and nomor_nota {$nomor_nota} during finalization attempt.");
            return redirect()->route('so_by_selected.index')
                ->with('error_message', 'Sesi Stock Opname dengan nota tersebut tidak ditemukan untuk difinalisasi.');
        }

        $stockOpnameEventId = $contextEntry->stock_opname_event_id; // This can be null
        $currentEvent = $stockOpnameEventId ? StockOpnameEvent::find($stockOpnameEventId) : null;
        $soContextName = $currentEvent ? $currentEvent->name : 'SO Rak';

        // Cek apakah sudah difinalisasi sebelumnya untuk nomor nota ini
        $alreadyFinalized = StockAudit::where('user_id', $userId)
            ->where('stock_opname_event_id', $stockOpnameEventId) // Match null if it's null
            ->where('nomor_nota', $nomor_nota)
            ->exists();
        if ($alreadyFinalized) {
            return redirect()->route('so_by_selected.index') // Atau ke halaman laporan
                ->with('info_message', "Sesi SO '{$soContextName}' dengan Nota '{$nomor_nota}' sudah difinalisasi sebelumnya.");
        }

        $tempEntries = TempStockEntry::where('user_id', $userId)
            ->where('stock_opname_event_id', $stockOpnameEventId) // Match null if it's null
            ->where('nomor_nota', $nomor_nota)
            ->whereNotNull('physical_stock') // Pastikan hanya yang sudah diinput yang diproses
            ->get();
        if ($tempEntries->isEmpty()) {
            return redirect()->back()->with('error_message', 'Tidak ada data entri stok yang valid untuk difinalisasi (Nota: ' . $nomor_nota . ').');
        }

        DB::beginTransaction();
        try {
            foreach ($tempEntries as $tempEntry) {
                StockAudit::create([
                    'user_id' => $tempEntry->user_id,
                    'stock_opname_event_id' => $tempEntry->stock_opname_event_id,
                    'nomor_nota' => $tempEntry->nomor_nota,
                    'product_id' => $tempEntry->product_id,
                    'product_name' => $tempEntry->product_name,
                    'barcode' => $tempEntry->barcode,
                    'product_code' => $tempEntry->product_code,
                    'system_stock' => $tempEntry->system_stock,
                    'physical_stock' => $tempEntry->physical_stock,
                    'difference' => $tempEntry->difference,
                    'audit_timestamp' => now(),
                ]);
            }

            // If this was an event-based SO, consider changing its status
            if ($currentEvent) {
                // Logic to determine if the event is fully completed can be complex.
                // For now, let's assume 'counted' is a suitable status after a nota is finalized.
                // Or, if all products for the event under this user/nota are done, then 'completed'.
                // This might need more sophisticated logic based on business rules.
                // $currentEvent->status = 'counted'; // or 'completed'
                // $currentEvent->save();
            }

            DB::commit();
            Log::info("Sesi SO '{$soContextName}' (Event ID: {$stockOpnameEventId}) dengan Nota {$nomor_nota} berhasil difinalisasi oleh User ID {$userId}.");
            return redirect()->route('so_by_selected.index') // Atau ke halaman laporan audit
                ->with('success_message', "Stock Opname untuk '{$soContextName}' (Nota: {$nomor_nota}) berhasil difinalisasi.");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Gagal finalisasi Sesi SO '{$soContextName}' (Event ID: {$stockOpnameEventId}, Nota: {$nomor_nota}) oleh User ID {$userId}: " . $e->getMessage());
            return redirect()->back()->with('error_message', 'Gagal memfinalisasi Stock Opname: ' . $e->getMessage());
        }
    }

    /**
     * Update physical stock from the differences page.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $nomor_nota The nomor_nota from the route
     * @return \Illuminate\Http\JsonResponse
     */
    // Method signature updated to reflect $nomor_nota from route
    public function updatePhysicalStockFromDifferences(Request $request, string $nomor_nota)
    {
        $validator = \Validator::make($request->all(), [
            'product_id' => 'required|integer|exists:products,id',
            'physical_stock' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        try {
            $userId = \Auth::id();
            // Ambil contextEntry sekali saja
            $contextEntry = TempStockEntry::where('user_id', $userId)
                ->where('nomor_nota', $nomor_nota)
                ->select('stock_opname_event_id')
                ->first();

            if (!$contextEntry) {
                return response()->json(['success' => false, 'message' => 'Sesi Stock Opname tidak ditemukan untuk nomor nota ini.'], 404);
            }

            // Ambil tempEntry langsung dengan filter lengkap
            $tempEntry = TempStockEntry::where([
                ['stock_opname_event_id', '=', $contextEntry->stock_opname_event_id],
                ['nomor_nota', '=', $nomor_nota],
                ['product_id', '=', $request->product_id],
                ['user_id', '=', $userId],
            ])->first();
            if (!$tempEntry) {
                return response()->json(['success' => false, 'message' => 'Entri stok sementara tidak ditemukan untuk produk ini pada nota dan event tersebut.'], 404);
            }

            // Ambil system_stock terbaru hanya jika diperlukan (misal, jika ingin selalu update dari user_product_stock)
            // $systemStock = UserProductStock::where('user_id', $userId)
            //     ->where('product_id', $request->product_id)
            //     ->value('stock') ?? $tempEntry->system_stock;
            // $tempEntry->system_stock = $systemStock;
            // $tempEntry->difference = $request->physical_stock - $systemStock;
            // Jika ingin tetap pakai system_stock yang sudah ada di tempEntry:
            $tempEntry->physical_stock = $request->physical_stock;
            $tempEntry->difference = $tempEntry->physical_stock - $tempEntry->system_stock;
            $tempEntry->save();

            return response()->json(['success' => true, 'message' => 'Stok fisik berhasil diperbarui.']);
        } catch (\Exception $e) {
            \Log::error('Error updating physical stock from differences page: ' . $e->getMessage(), [
                'exception' => $e,
                'nomor_nota' => $nomor_nota,
                'user_id' => $userId,
                'request_data' => $request->all()
            ]);
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan pada server saat memperbarui stok. Silakan periksa log aplikasi untuk detail.'], 500);
        }
    }
}

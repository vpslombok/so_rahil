<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StockOpnameEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; // Tambahkan ini

class SelectableSoEventController extends Controller
{
    /**
     * Mengambil daftar Stock Opname Event yang dapat dipilih oleh pengguna.
     * Hanya event yang berstatus 'active' yang akan ditampilkan.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // Autentikasi sudah ditangani oleh middleware 'auth:sanctum' pada grup rute.
        // $user = Auth::user(); // Tidak perlu mengambil user jika tidak digunakan secara spesifik.

        $activeSoEvents = StockOpnameEvent::where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'start_date', 'end_date', 'status']);

        return response()->json([
            'message' => 'Daftar event stock opname aktif berhasil diambil.',
            'data' => $activeSoEvents
        ]);
    }

    /**
     * Menampilkan data stock_audits berdasarkan tanggal hari ini untuk user yang sedang login.
     * Jika belum ada, tampilkan alert silakan pilih SO terlebih dahulu.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTodayStockAudits(Request $request)
    {
        $user = Auth::user();

        // Ambil semua TempStockEntry milik user yang BELUM ada di StockAudit (belum difinalisasi)
        $unfinalizedStockAudits = \App\Models\TempStockEntry::where('user_id', $user->id)
            ->whereNotExists(function (
                $query
            ) {
                $query->select(DB::raw(1))
                    ->from('stock_audits')
                    ->whereColumn('stock_audits.user_id', 'temp_stock_entries.user_id')
                    ->whereColumn('stock_audits.nomor_nota', 'temp_stock_entries.nomor_nota')
                    ->whereRaw('stock_audits.stock_opname_event_id <=> temp_stock_entries.stock_opname_event_id');
            })
            ->get();

        if ($unfinalizedStockAudits->isEmpty()) {
            return response()->json([
                'message' => 'Tidak ada data SO yang belum difinalisasi.',
                'data' => []
            ], 200);
        }

        // Ambil semua product_id yang ada di unfinalizedStockAudits
        $productIds = $unfinalizedStockAudits->pluck('product_id')->unique()->toArray();
        // Ambil stok terbaru dari user_product_stock sekaligus (minimalkan query N+1)
        $userProductStocks = \App\Models\UserProductStock::where('user_id', $user->id)
            ->whereIn('product_id', $productIds)
            ->pluck('stock', 'product_id');

        // Pastikan setiap field null diubah menjadi 0 dan tambahkan system_stock terbaru
        $processedStockAudits = $unfinalizedStockAudits->map(function ($item) use ($userProductStocks) {
            foreach ($item->getAttributes() as $key => $value) {
                if (is_null($value)) {
                    $item->$key = 0;
                }
            }
            // Overwrite/isi system_stock dengan data terbaru dari user_product_stock
            $item->system_stock = $userProductStocks[$item->product_id] ?? 0;
            // Hitung difference langsung dari physical_stock dan system_stock terbaru
            $item->difference = $item->physical_stock - $item->system_stock;
            return $item;
        });

        // Urutkan berdasarkan nama produk (product_name) secara alfabet
        $sortedStockAudits = $processedStockAudits->sortBy(function ($item) {
            return strtolower($item->product_name ?? '');
        })->values();

        return response()->json([
            'message' => 'Data SO yang belum difinalisasi berhasil diambil.',
            'data' => $sortedStockAudits
        ], 200);
    }

    //fungsi update stock entry
    public function updateStockEntry(Request $request)
    {
        // Validasi input
        $validated = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'stock_opname_event_id' => 'required|integer|exists:stock_opname_events,id',
            'physical_stock' => 'required|integer|min:0',
            'nomor_nota' => 'nullable|string',
        ]);

        $user = Auth::user();

        // Update atau buat entri di TempStockEntry
        $entry = \App\Models\TempStockEntry::updateOrCreate(
            [
                'user_id' => $user->id,
                'product_id' => $validated['product_id'],
                'stock_opname_event_id' => $validated['stock_opname_event_id'],
                'nomor_nota' => $validated['nomor_nota'] ?? null,
            ],
            [
                'physical_stock' => $validated['physical_stock'],
            ]
        );

        // Ambil system_stock terbaru dari user_product_stock
        $systemStock = \App\Models\UserProductStock::where('user_id', $user->id)
            ->where('product_id', $validated['product_id'])
            ->value('stock') ?? 0;
        // Hitung difference
        $difference = $validated['physical_stock'] - $systemStock;

        // Pastikan data null menjadi 0 pada response dan tambahkan system_stock & difference
        $data = collect($entry->getAttributes())->map(function ($v) {
            return is_null($v) ? 0 : $v;
        })->toArray();
        $data['system_stock'] = $systemStock;
        $data['difference'] = $difference;

        return response()->json([
            'message' => 'Entri stok berhasil diperbarui.',
            'data' => $data
        ], 200);
    }

    /**
     * Menyimpan pilihan SO (Stock Opname Event) oleh user untuk persiapan entry stok fisik.
     * Tidak membuat TempStockEntry kosong, hanya mengembalikan nomor_nota dan event_id.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function selectSoForEntry(Request $request)
    {
        try {
            $user = Auth::user();
            $validated = $request->validate([
                'stock_opname_event_id' => 'required|integer|exists:stock_opname_events,id',
            ]);
            $eventId = $validated['stock_opname_event_id'];
            $angkaacak = rand(1000, 9999); // Angka acak untuk nomor nota
            $today = now()->format('dmY');
            $nomorNota = $today . '-' . $user->username . '-' . $angkaacak;

            $event = StockOpnameEvent::find($eventId);
            // Ambil produk yang terkait dengan event dari tabel so_selected_products beserta relasi product
            $selectedProducts = \App\Models\SoSelectedProduct::where('stock_opname_event_id', $eventId)
                ->with('product')
                ->get();

            $productDetails = $selectedProducts->map(function ($item) {
                $product = $item->product;
                if (!$product) return null; // skip jika relasi tidak ada
                return [
                    'so_selected_product_id' => $item->id,
                    'product_id' => $product->id,
                    'product_code' => $product->product_code,
                    'barcode' => $product->barcode,
                    'name' => $product->name,
                    'stock' => $product->stock ?? '0',
                    'category' => $product->category ?? '-',
                    'price' => $product->price ?? 0,
                    'description' => $product->description ?? '-',
                ];
            })->filter()->values();

            return response()->json([
                'message' => 'SO berhasil dipilih dan sesi entry stok fisik siap.',
                'data' => [
                    'stock_opname_event_id' => $eventId,
                    'nomor_nota' => $nomorNota,
                    'event_detail' => $event,
                    'products' => $productDetails
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * Simpan data produk berdasarkan item SO Event (bulk insert/update stok fisik untuk semua produk pada event terpilih).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveProductsForSoEvent(Request $request)
    {
        try {
            $user = Auth::user();
            // Cek apakah masih ada SO event yang belum difinalisasi untuk user ini
            $unfinalized = \App\Models\TempStockEntry::where('user_id', $user->id)
                ->select('stock_opname_event_id', 'nomor_nota')
                ->distinct()
                ->get()
                ->filter(function ($item) use ($user) {
                    return !\App\Models\StockAudit::where('user_id', $user->id)
                        ->where('stock_opname_event_id', $item->stock_opname_event_id)
                        ->where('nomor_nota', $item->nomor_nota)
                        ->exists();
                });
            if ($unfinalized->count() > 0) {
                return response()->json([
                    'message' => 'Masih terdapat SO Event yang belum difinalisasi. Selesaikan/finalisasi terlebih dahulu sebelum membuat entri baru.',
                    'unfinalized' => $unfinalized->values()
                ], 403);
            }

            $validated = $request->validate([
                'stock_opname_event_id' => 'required|integer|exists:stock_opname_events,id',
                'nomor_nota' => 'required|string',
                'products' => 'required|array',
                'products.*.product_id' => 'required|integer|exists:products,id',
                'products.*.physical_stock' => 'required|integer|min:0',
            ]);
            $eventId = $validated['stock_opname_event_id'];
            $nomorNota = $validated['nomor_nota'];
            $products = $validated['products'];

            $result = [];
            foreach ($products as $product) {
                // Ambil nama produk dari tabel products
                $productModel = \App\Models\Product::find($product['product_id']);
                $productName = $productModel ? $productModel->name : '';
                // Ambil system_stock dari tabel user_product_stock jika ada, jika tidak 0
                $systemStock = 0;
                $userProductStock = \App\Models\UserProductStock::where('user_id', $user->id)
                    ->where('product_id', $product['product_id'])
                    ->first();
                if ($userProductStock) {
                    $systemStock = $userProductStock->stock;
                }
                $entry = \App\Models\TempStockEntry::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'product_id' => $product['product_id'],
                        'stock_opname_event_id' => $eventId,
                        'nomor_nota' => $nomorNota,
                    ],
                    [
                        'physical_stock' => $product['physical_stock'],
                        'product_name' => $productName,
                        'system_stock' => $systemStock,
                        'barcode' => $productModel->barcode ?? '',
                        'product_code' => $productModel->product_code ?? '',
                        'difference' => $product['physical_stock'] - $systemStock,
                    ]
                );
                $result[] = $entry;
            }

            // Pastikan data null menjadi 0 pada response
            $data = collect($result)->map(function ($item) {
                foreach ($item->getAttributes() as $key => $value) {
                    if (is_null($value)) {
                        $item->$key = 0;
                    }
                }
                return $item;
            });

            // Simpan response ke log
            \Log::info('[saveProductsForSoEvent] Response', [
                'user_id' => $user->id,
                'event_id' => $eventId,
                'nomor_nota' => $nomorNota,
                'response_data' => $data
            ]);

            return response()->json([
                'message' => 'Data produk untuk SO Event berhasil disimpan.',
                'data' => $data
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Log error validasi
            \Log::warning('[saveProductsForSoEvent] Validation Error', [
                'errors' => $e->errors(),
                'input' => $request->all()
            ]);
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('[saveProductsForSoEvent] Server Error', [
                'error' => $e->getMessage(),
                'input' => $request->all()
            ]);
            return response()->json([
                'message' => 'Server Error',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ambil daftar SO Event yang BELUM difinalisasi oleh user login (berdasarkan nomor_nota yang belum ada di stock_audits).
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUnfinalizedSoEvents(Request $request)
    {
        $user = Auth::user();
        // Ambil semua nomor_nota dari temp_stock_entries milik user
        $userNota = \App\Models\TempStockEntry::where('user_id', $user->id)
            ->select('stock_opname_event_id', 'nomor_nota')
            ->distinct()
            ->get();

        // Ambil semua nomor_nota yang sudah difinalisasi (ada di stock_audits)
        $finalizedNota = \App\Models\StockAudit::where('user_id', $user->id)
            ->select('stock_opname_event_id', 'nomor_nota')
            ->distinct()
            ->get();

        // Buat array untuk pencocokan cepat
        $finalizedSet = $finalizedNota->map(function ($item) {
            return $item->stock_opname_event_id . '|' . $item->nomor_nota;
        })->toArray();

        // Filter nomor_nota yang belum difinalisasi
        $unfinalized = $userNota->filter(function ($item) use ($finalizedSet) {
            $key = $item->stock_opname_event_id . '|' . $item->nomor_nota;
            return !in_array($key, $finalizedSet);
        })->values();

        // Ambil detail event
        $eventIds = $unfinalized->pluck('stock_opname_event_id')->unique()->filter();
        $events = $eventIds->isNotEmpty()
            ? \App\Models\StockOpnameEvent::whereIn('id', $eventIds)->get()
            : collect();

        return response()->json([
            'message' => 'Daftar SO Event yang belum difinalisasi.',
            'data' => [
                'unfinalized' => $unfinalized,
                'events' => $events
            ]
        ]);
    }

    /**
     * Finalisasi hasil SO Event: simpan seluruh data TempStockEntry user ke tabel stock_audits.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function finalizeSoEvent(Request $request)
    {
        $validated = $request->validate([
            'stock_opname_event_id' => 'required|integer|exists:stock_opname_events,id',
            'nomor_nota' => 'required|string',
            'note' => 'nullable|string',
        ]);
        $user = Auth::user();
        $eventId = $validated['stock_opname_event_id'];
        $nomorNota = $validated['nomor_nota'];
        $note = $validated['note'] ?? null;

        // Cek apakah sudah pernah difinalisasi
        $alreadyFinalized = \App\Models\StockAudit::where('user_id', $user->id)
            ->where('stock_opname_event_id', $eventId)
            ->where('nomor_nota', $nomorNota)
            ->exists();
        if ($alreadyFinalized) {
            return response()->json([
                'message' => 'SO Event sudah difinalisasi sebelumnya.',
                'success' => false
            ], 409);
        }

        // Ambil semua temp entry yang sudah diinput untuk event & nota ini
        $tempEntries = \App\Models\TempStockEntry::where('user_id', $user->id)
            ->where('stock_opname_event_id', $eventId)
            ->where('nomor_nota', $nomorNota)
            ->whereNotNull('physical_stock')
            ->get();
        if ($tempEntries->isEmpty()) {
            return response()->json([
                'message' => 'Tidak ada data entri stok yang valid untuk difinalisasi.',
                'success' => false
            ], 422);
        }

        \DB::beginTransaction();
        try {
            foreach ($tempEntries as $tempEntry) {
                \App\Models\StockAudit::create([
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
                    'note' => $note,
                ]);
            }
            \DB::commit();
            return response()->json([
                'message' => 'Finalisasi SO Event berhasil. Data sudah disimpan ke stock_audits.',
                'success' => true
            ], 200);
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('[finalizeSoEvent] Gagal finalisasi: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'event_id' => $eventId,
                'nomor_nota' => $nomorNota
            ]);
            return response()->json([
                'message' => 'Gagal finalisasi SO Event: ' . $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    /**
     * Mengambil data SO Event yang sudah difinalisasi oleh user login.
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFinalizedSoEvents(Request $request)
    {
        $user = Auth::user();
        // Ambil semua stock_audits milik user, group by event & nomor_nota
        $audits = \App\Models\StockAudit::where('user_id', $user->id)
            ->orderByDesc('checked_at')
            ->get();

        // Group by event & nomor_nota
        $grouped = $audits->groupBy(function ($item) {
            return $item->stock_opname_event_id . '|' . $item->nomor_nota;
        });

        $result = [];
        foreach ($grouped as $key => $entries) {
            $first = $entries->first();
            $event = null;
            if ($first->stock_opname_event_id) {
                try {
                    $event = \App\Models\StockOpnameEvent::find($first->stock_opname_event_id);
                } catch (\Exception $e) {
                    $event = null;
                }
            }
            $result[] = [
                'stock_opname_event_id' => $first->stock_opname_event_id,
                'nomor_nota' => $first->nomor_nota,
                'event_detail' => $event ? $event : (object)[],
                'finalized_at' => $first->checked_at ? \Carbon\Carbon::parse($first->checked_at)->format('Y-m-d H:i') : null,
                'note' => $first->note ?? 'tidak ada',
                'products' => $entries->map(function ($item) {
                    // Lengkapi data produk jika null
                    $productName = $item->product_name;
                    $barcode = $item->barcode;
                    $productCode = $item->product_code;
                    if (is_null($productName) || is_null($barcode) || is_null($productCode)) {
                        $product = \App\Models\Product::find($item->product_id);
                        if ($product) {
                            if (is_null($productName)) $productName = $product->name;
                            if (is_null($barcode)) $barcode = $product->barcode;
                            if (is_null($productCode)) $productCode = $product->product_code;
                        }
                    }
                    return [
                        'product_id' => $item->product_id,
                        'product_name' => $productName,
                        'barcode' => $barcode,
                        'product_code' => $productCode,
                        'system_stock' => $item->system_stock,
                        'physical_stock' => $item->physical_stock,
                        'difference' => $item->difference
                    ];
                })->values()->all(),
            ];
        }

        return response()->json([
            'message' => 'Daftar SO Event yang sudah difinalisasi.',
            'data' => $result
        ], 200);
    }

    /**
     * Hapus data stock_audits berdasarkan event & nomor_nota milik user login.
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteFinalizedSoEvent(Request $request)
    {
        $validated = $request->validate([
            'stock_opname_event_id' => 'required|integer',
            'nomor_nota' => 'required|string',
        ]);
        $user = Auth::user();
        $eventId = $validated['stock_opname_event_id'];
        $nomorNota = $validated['nomor_nota'];

        $deleted = \App\Models\StockAudit::where('user_id', $user->id)
            ->where('stock_opname_event_id', $eventId)
            ->where('nomor_nota', $nomorNota)
            ->delete();

        if ($deleted > 0) {
            return response()->json([
                'message' => 'Data finalisasi SO Event berhasil dihapus.',
                'success' => true
            ], 200);
        } else {
            return response()->json([
                'message' => 'Data tidak ditemukan atau sudah dihapus.',
                'success' => false
            ], 404);
        }
    }
}

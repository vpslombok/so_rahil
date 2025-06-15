<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\UserProductStock;
use App\Models\StockOpnameEvent;
use App\Models\Rack; // Tambahkan model Rack
use App\Models\SoSelectedProduct;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class UserStockController extends Controller
{
    /**
     * Mengambil data stok produk pengguna, dengan opsi filter berdasarkan event stock opname.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProductStock(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'stock_opname_event_id' => 'nullable|integer|exists:stock_opname_events,id',
            'rack_id' => 'nullable|integer|exists:racks,id', // Tambahkan validasi untuk rack_id
            'search' => 'nullable|string|max:255', // Validasi untuk parameter pencarian
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $stockOpnameEventId = $request->input('stock_opname_event_id');
        $rackId = $request->input('rack_id');
        $searchTerm = $request->input('search'); // Ambil input pencarian
        $results = collect();

        if ($stockOpnameEventId) {
            // Logika ketika event stock opname spesifik diminta
            $event = StockOpnameEvent::find($stockOpnameEventId);
            if (!$event) {
                // Seharusnya tidak terjadi karena validasi 'exists', tapi sebagai pengaman
                return response()->json(['message' => 'Event tidak ditemukan.'], 404);
            }

            // Ambil semua produk yang terpilih untuk event ini, beserta detail produk dan raknya
            $selectedProductsQuery = SoSelectedProduct::where('stock_opname_event_id', $stockOpnameEventId)
                ->with([
                    // Eager load product dengan kolom spesifik dan filter
                    'product' => function ($query) use ($rackId, $searchTerm) {
                        $query->select('id', 'name', 'product_code', 'barcode', 'rack_id'); // Pastikan barcode dipilih
                        if ($rackId) {
                            $query->where('rack_id', $rackId);
                        }
                        // Kondisi pencarian di sini memastikan data produk yang di-load sudah terfilter,
                        // meskipun whereHas di bawah yang utama untuk memfilter SoSelectedProduct.
                        if ($searchTerm) {
                            $query->where(function ($q) use ($searchTerm) {
                                $q->where('name', 'like', '%' . $searchTerm . '%')
                                  ->orWhere('product_code', 'like', '%' . $searchTerm . '%');
                            });
                        }
                    },
                    'product.rack:id,name'                  // Eager load rack dari product
                ])
                // Filter SoSelectedProduct berdasarkan produk yang cocok dengan rack_id dan searchTerm
                ->whereHas('product', function ($query) use ($rackId, $searchTerm) {
                    if ($rackId) {
                        $query->where('rack_id', $rackId);
                    }
                    if ($searchTerm) {
                        $query->where(function ($q) use ($searchTerm) {
                            $q->where('name', 'like', '%' . $searchTerm . '%')
                              ->orWhere('product_code', 'like', '%' . $searchTerm . '%');
                        });
                    }
                });
            $selectedProductsForEvent = $selectedProductsQuery->get();

            if ($selectedProductsForEvent->isEmpty()) {
                return response()->json([
                    'message' => 'Tidak ada produk yang terdaftar untuk event ini atau sesuai kriteria filter.',
                    'data' => []
                ], 200);
            }

            // Ambil ID produk untuk query UserProductStock
            $productIdsInEvent = $selectedProductsForEvent->pluck('product.id')->unique()->filter();

            if($productIdsInEvent->isNotEmpty()){
                // Ambil entri UserProductStock TERBARU yang sudah ada untuk user untuk produk-produk dalam event ini.
                $userStocksForEventProducts = UserProductStock::where('user_id', $user->id)
                    ->whereIn('product_id', $productIdsInEvent)
                    ->orderBy('updated_at', 'desc')
                    ->get();

                // Buat map dari product_id ke entri stok terbaru
                $existingUserStocksMap = collect();
                foreach ($userStocksForEventProducts as $entry) {
                    if (!$existingUserStocksMap->has($entry->product_id)) {
                        $existingUserStocksMap->put($entry->product_id, $entry);
                    }
                }

                $results = $selectedProductsForEvent->map(function ($selectedProductInfo) use ($existingUserStocksMap, $event) {
                    $product = $selectedProductInfo->product;

                    if (!$product) { // Seharusnya tidak terjadi karena whereHas, tapi sebagai pengaman
                        return null;
                    }

                    $userStockEntry = $existingUserStocksMap->get($product->id);

                    return [
                        'id' => $userStockEntry ? $userStockEntry->id : null,
                        'product_id' => $product->id,
                        'product_barcode' => $product->barcode ?? null,
                        'product_code' => $product->product_code,
                        'product_name' => $product->name,
                        'rack_name' => $product->rack->name ?? null,
                        'stock' => $userStockEntry ? $userStockEntry->stock : 0,
                        'stock_opname_event_id' => $event->id,
                        'stock_opname_event_name' => $event->name,
                        'last_updated' => $userStockEntry ? $userStockEntry->updated_at->toDateTimeString() : null,
                    ];
                })->filter()->sortBy('product_name')->values();
            } else {
                 $results = collect(); // Tidak ada produk yang cocok setelah filter
            }
        } else {
            // Logika ketika tidak ada event_id spesifik: tampilkan SEMUA produk,
            // dengan stok terakhir yang diinput user (jika ada), atau 0 jika tidak ada.
            $allProductsQuery = Product::with('rack:id,name') // Eager load relasi rack
                ->select('id', 'name', 'barcode', 'product_code', 'rack_id') // Pilih kolom yang dibutuhkan
                ->orderBy('name'); // Urutkan produk berdasarkan nama

            if ($rackId) {
                $allProductsQuery->where('rack_id', $rackId); // Filter berdasarkan rack_id jika ada
            }

            if ($searchTerm) {
                $allProductsQuery->where(function ($q) use ($searchTerm) {
                    $q->where('name', 'like', '%' . $searchTerm . '%')
                      ->orWhere('product_code', 'like', '%' . $searchTerm . '%');
                });
            }
            $allProducts = $allProductsQuery->get();

            // Ambil semua entri UserProductStock untuk pengguna ini,
            // diurutkan agar entri terbaru untuk setiap produk bisa diambil.

            // Ambil semua entri UserProductStock untuk pengguna ini,
            // diurutkan agar entri terbaru untuk setiap produk bisa diambil.
            // Menghapus with('stockOpnameEvent') karena foreign key 'stock_opname_event_id' tidak ada di user_product_stock
            $userStockEntries = UserProductStock::where('user_id', $user->id)
                ->orderBy('product_id') // Urutkan berdasarkan product_id
                ->orderBy('updated_at', 'desc') // Kemudian urutkan berdasarkan tanggal update terbaru
                ->get();

            // Buat map untuk pencarian cepat entri stok terbaru per produk
            $latestUserStocksMap = collect();
            foreach ($userStockEntries as $entry) {
                // Karena sudah diurutkan, entri pertama yang ditemui untuk product_id adalah yang terbaru
                if (!$latestUserStocksMap->has($entry->product_id)) {
                    $latestUserStocksMap->put($entry->product_id, $entry);
                }
            }

            $results = $allProducts->map(function ($product) use ($latestUserStocksMap) {
                $latestStockEntry = $latestUserStocksMap->get($product->id);

                return [
                    // ID dari UserProductStock jika ada, null jika tidak
                    'id' => $latestStockEntry ? $latestStockEntry->id : null,
                    'product_id' => $product->id,
                    'product_barcode' => $product->barcode ?? null, // Tambahkan barcode jika ada
                    'product_code' => $product->product_code,
                    'product_name' => $product->name,
                    'rack_name' => $product->rack->name ?? null,
                    // Stok aktual dari entri terbaru, atau 0 jika tidak ada entri
                    'stock' => $latestStockEntry ? $latestStockEntry->stock : 0,
                    // Detail event dari entri stok terbaru, atau null jika tidak ada entri
                    'stock_opname_event_id' => null, // Tidak bisa diambil dari UserProductStock jika kolom tidak ada
                    'stock_opname_event_name' => null, // Tidak bisa diambil dari UserProductStock jika relasi tidak valid
                    'last_updated' => $latestStockEntry ? $latestStockEntry->updated_at->toDateTimeString() : null,
                ];
            });
        }
        return response()->json([
            'message' => 'Data stok berhasil diambil.',
            'data' => $results
        ]);
    }

    /**
     * Mengambil daftar produk yang belum diinput stoknya oleh pengguna untuk event SO aktif.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPendingSubmissionsForActiveEvent(Request $request)
    {
        $user = Auth::user();
        $activeEvent = StockOpnameEvent::where('status', 'active')->first();

        if (!$activeEvent) {
            return response()->json([
                'message' => 'Tidak ada event stock opname yang aktif saat ini.',
                'data' => []
            ], 404);
        }

        $eventProductIds = SoSelectedProduct::where('stock_opname_event_id', $activeEvent->id)
            ->pluck('product_id');

        if ($eventProductIds->isEmpty()) {
            return response()->json([
                'message' => 'Tidak ada produk yang terdaftar untuk event aktif ini.',
                'active_event_id' => $activeEvent->id,
                'active_event_name' => $activeEvent->name,
                'data' => []
            ], 200);
        }

        // Cek produk mana yang sudah pernah diinput stoknya oleh user (tidak spesifik event ini,
        // tapi untuk produk yang ada di event aktif)
        $submittedProductIds = UserProductStock::where('user_id', $user->id)
            ->whereIn('product_id', $eventProductIds) // Filter by products in the current active event
            ->pluck('product_id')->unique(); // Pastikan unik jika produk memiliki banyak entri stok

        $pendingProductIds = $eventProductIds->diff($submittedProductIds);

        if ($pendingProductIds->isEmpty()) {
            return response()->json([
                'message' => 'Semua produk untuk event aktif telah diinput oleh Anda.',
                 'active_event_id' => $activeEvent->id,
                'active_event_name' => $activeEvent->name,
                'data' => []
            ], 200);
        }

        $pendingProducts = Product::whereIn('id', $pendingProductIds)
            ->with('rack:id,name')
            ->select(['id', 'name', 'product_code', 'rack_id'])
            ->get()
            ->map(function ($product) use ($activeEvent) {
                return [
                    'product_id' => $product->id,
                    'product_code' => $product->product_code,
                    'product_name' => $product->name,
                    'rack_name' => $product->rack->name ?? null,
                ];
            });

        return response()->json([
            'message' => 'Daftar produk yang belum diinput untuk event aktif berhasil diambil.',
            'active_event' => [
                'id' => $activeEvent->id,
                'name' => $activeEvent->name,
            ],
            'data' => $pendingProducts
        ]);
    }

    /**
     * Memperbarui atau membuat entri stok produk untuk pengguna.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStock(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'product_id' => 'required|integer|exists:products,id',
            'quantity' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $userStock = UserProductStock::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'product_id' => $request->product_id,
                    ],
                [
                    'stock' => $request->quantity, // Menggunakan 'stock' sesuai model UserProductStock
                ] 
            );

            $userStock->load('product:id,name,product_code'); // Menggunakan product_code

            return response()->json([
                'message' => 'Stok produk berhasil diperbarui.',
                'data' => [
                    'id' => $userStock->id,
                    'product_id' => $userStock->product_id,
                    'product_name' => $userStock->product->name ?? null, // Tambahkan null coalescing
                    'stock' => $userStock->stock, // Menggunakan 'stock' sesuai model
                    // Mengambil event_id dari request karena tidak disimpan di userStock
                    'stock_opname_event_id' => (int)$request->stock_opname_event_id, 
                    'updated_at' => $userStock->updated_at->toDateTimeString(),
                ]
            ], 200);

        } catch (\Exception $e) {
            // Sebaiknya log error di sini: \Log::error('Error updating stock: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal memperbarui stok produk. Terjadi kesalahan server.'], 500);
        }
    }

    /**
     * Mengambil data untuk opsi filter: daftar rak dan event SO aktif.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFilterOptions(Request $request)
    {
        $racks = Rack::select('id', 'name')->orderBy('name')->get();
        $activeStockOpnameEvents = StockOpnameEvent::where('status', 'active')
                                        ->select('id', 'name')
                                        ->orderBy('name')
                                        ->get();

        return response()->json([
            'message' => 'Data opsi filter berhasil diambil.',
            'data' => [
                'racks' => $racks,
                'stock_opname_events' => $activeStockOpnameEvents,
            ]
        ]);
    }
}

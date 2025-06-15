<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Imports\ProductsImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\StockOpnameEvent; // Tambahkan ini
use App\Models\SoSelectedProduct; // Tambahkan ini
use App\Models\Rack; // Tambahkan ini
use App\Models\UserProductStock;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display product listing
     */
    public function index(Request $request)
    {
        $currentUser = Auth::user();
        $selectedUserId = $request->input('user_id_filter');
        $searchQuery = $request->input('search_product');
        $selectedEventId = $request->input('event_id_filter'); // Tambahkan filter event
        $selectedRackId = $request->input('rack_id_filter'); // Tambahkan filter rak
        $users_for_filter = [];
        $activeSoEvents = StockOpnameEvent::where('status', 'active')->orderBy('name')->get(); // Ambil event aktif
        $racks = Rack::orderBy('name')->get(); // Ambil semua rak untuk filter

        // Base query
        $query = Product::query()->orderBy('name');

        if ($currentUser->role == 'admin') {
            $users_for_filter = User::where('role', '!=', 'admin')
                ->orderBy('username')->get();
        } else {
            $selectedUserId = $currentUser->id;
        }

        // Terapkan filter pencarian produk jika ada
        if ($searchQuery) {
            $query->where(function ($q) use ($searchQuery) {
                $q->where('name', 'like', "%{$searchQuery}%")
                    ->orWhere('product_code', 'like', "%{$searchQuery}%")
                    ->orWhere('barcode', 'like', "%{$searchQuery}%");
            });
        }

        // Filter berdasarkan SO Event jika dipilih
        if ($selectedEventId) {
            $productIdsInEvent = SoSelectedProduct::where('stock_opname_event_id', $selectedEventId)
                ->pluck('product_id')
                ->toArray();
            if (!empty($productIdsInEvent)) {
                $query->whereIn('products.id', $productIdsInEvent);
            } else {
                // Jika event dipilih tapi tidak ada produk, tampilkan hasil kosong untuk produk
                $query->whereRaw('1 = 0'); // Kondisi yang selalu false
            }
        }

        // Filter berdasarkan Rak jika dipilih
        if ($selectedRackId) {
            $query->where('rack_id', $selectedRackId);
        }

        // Eager load user stocks with conditional
        $query->with(['userStocks' => function ($q) use ($selectedUserId) {
            $q->when($selectedUserId, fn($q) => $q->where('user_id', $selectedUserId));
        }]);
        $query->with('rack'); // Pastikan relasi rack di-load untuk ditampilkan

        $products_data = $query->paginate(5);
        return view('index', compact('products_data', 'users_for_filter', 'selectedUserId', 'searchQuery', 'activeSoEvents', 'selectedEventId', 'racks', 'selectedRackId'));
    }

    /**
     * Store a new product
     */
    public function store(Request $request)
    {
        // Ambil parameter filter dari request untuk redirect
        $redirectParams = $request->only([
            'user_id_filter',
            'event_id_filter',
            'rack_id_filter',
            'search_product',
            // 'page' biasanya tidak relevan setelah menambah produk baru, bisa diabaikan atau diset ke 1
        ]);
        $validator = Validator::make($request->all(), [
            'barcode' => 'required|string|max:50|unique:products,barcode',
            'product_code' => 'required|string|max:50|unique:products,product_code',
            'name' => 'required|string|max:100',
            'price' => 'nullable|numeric|min:0',
        ], [
            'barcode.required' => 'Barcode wajib diisi.',
            'barcode.unique' => 'Barcode sudah terdaftar.',
            'product_code.required' => 'Kode produk wajib diisi.',
            'product_code.unique' => 'Kode produk sudah terdaftar.',
            'name.required' => 'Nama produk wajib diisi.',
        ]);

        if ($validator->fails()) {
            return redirect()->route('dashboard', array_filter($redirectParams))
                ->withErrors($validator, 'addProduct')
                ->withInput();
        }

        try {
            DB::transaction(function () use ($validator) {
                $product = Product::create($validator->validated());

                if (Auth::user()->role != 'admin') {
                    UserProductStock::create([
                        'user_id' => Auth::id(),
                        'product_id' => $product->id,
                        'stock' => 0,
                    ]);
                }
            });

            return redirect()->route('dashboard', array_filter($redirectParams))
                ->with('success_message_product', 'Produk berhasil ditambahkan!');
        } catch (\Exception $e) {
            Log::error('Error creating product: ' . $e->getMessage());
            return redirect()->route('dashboard', array_filter($redirectParams))
                ->with('error_message_product', 'Gagal menambahkan produk. Silakan coba lagi.')
                ->withInput();
        }
    }

    /**
     * Import products from Excel
     */
    public function importExcel(Request $request)
    {
        // Ambil parameter filter dari request untuk redirect
        $redirectParams = $request->only([
            'user_id_filter',
            'event_id_filter',
            'rack_id_filter',
            'search_product',
            // 'page' biasanya tidak relevan setelah import, bisa diabaikan atau diset ke 1
        ]);
        $request->validate(['excel_file' => 'required|mimes:xlsx,xls,csv']);

        try {
            $import = new ProductsImport();
            Excel::import($import, $request->file('excel_file'));

            if ($import->errors()->isNotEmpty()) {
                $errorMessages = $import->errors()->map(function ($error) {
                    $exception = $error->getException();
                    if ($exception instanceof \Maatwebsite\Excel\Validators\ValidationException) {
                        return collect($exception->failures())->map(function ($failure) {
                            $value = $failure->values()[$failure->attribute()] ?? '[NILAI TIDAK DITEMUKAN]';
                            return "Baris {$failure->row()}, Kolom '{$failure->attribute()}': " .
                                implode(", ", $failure->errors()) . " (Nilai: '{$value}')";
                        })->implode('<br>');
                    }
                    return "Baris {$error->row()}: " . $exception->getMessage();
                })->implode('<br>');

                return redirect()->route('dashboard', array_filter($redirectParams))
                    ->with('error_message_product', 'Beberapa data gagal diimpor:<br>' . $errorMessages);
            }

            return redirect()->route('dashboard', array_filter($redirectParams))
                ->with('success_message_product', 'Produk berhasil diimpor!');
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $errorMessages = collect($e->failures())->map(function ($failure) {
                $value = $failure->values()[$failure->attribute()] ?? '[NILAI TIDAK DITEMUKAN]';
                return "Baris {$failure->row()}, Kolom '{$failure->attribute()}': " .
                    implode(", ", $failure->errors()) . " (Nilai: '{$value}')";
            })->implode('<br>');

            return redirect()->route('dashboard', array_filter($redirectParams))
                ->with('error_message_product', 'Validasi gagal:<br>' . $errorMessages);
        } catch (\Exception $e) {
            Log::error('Import error: ' . $e->getMessage());
            return redirect()->route('dashboard', array_filter($redirectParams))
                ->with('error_message_product', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Update product
     */
    public function update(Request $request, Product $product)
    {
        $currentUser = Auth::user();
        // Parameter filter akan diambil dari $request->only() di bawah,
        // yang mencakup data dari body request (hidden inputs dari form).

        $rules = ['stock' => 'nullable|integer|min:0'];

        if ($currentUser->role == 'admin') {
            $rules = array_merge($rules, [
                'barcode' => 'required|string|max:50|unique:products,barcode,' . $product->id,
                'product_code' => 'required|string|max:50|unique:products,product_code,' . $product->id,
                'name' => 'required|string|max:100',
                'price' => 'nullable|numeric|min:0',
            ]);
        }

        $validator = Validator::make($request->all(), $rules);

        // Siapkan parameter redirect (untuk jaga filter tetap tersimpan)
        // Ambil semua parameter yang relevan dari request
        $redirectParams = [
            'user_id_filter' => $request->input('user_id_filter'),
            'event_id_filter' => $request->input('event_id_filter'),
            'rack_id_filter' => $request->input('rack_id_filter'), // Tambahkan ini
            'search_product' => $request->input('search_product'), // Sesuaikan dengan nama input di form/JS
            'page' => $request->input('page')
        ];

        if ($validator->fails()) {
            return redirect()->route('dashboard', array_filter($redirectParams))
                ->withErrors($validator, 'editProduct')
                ->withInput()
                ->with('open_edit_product_modal', $product->id);
        }

        try {
            // Hapus $selectedUserId dari 'use' karena kita akan mengambilnya dari $request->input() di dalam closure
            // untuk memastikan kita mendapatkan nilai yang disubmit dengan form.
            DB::transaction(function () use ($validator, $product, $currentUser, $request) {
                $validated = $validator->validated();
                $productDetailsToUpdate = [];

                if ($currentUser->role == 'admin') {
                    $productFields = ['barcode', 'product_code', 'name', 'price'];
                    foreach ($productFields as $field) {
                        if (array_key_exists($field, $validated)) {
                            $productDetailsToUpdate[$field] = $validated[$field];
                        }
                    }
                    if (!empty($productDetailsToUpdate)) {
                        $product->update($productDetailsToUpdate);
                    }
                }

                // Logika update stok
                if (array_key_exists('stock', $validated)) {
                    $userIdForStockUpdate = null;

                    if ($currentUser->role == 'admin') {
                        // Ambil user_id_filter dari data form yang disubmit
                        $userIdFilterFromForm = $request->input('user_id_filter');

                        if ($userIdFilterFromForm) {
                            $targetUser = User::find($userIdFilterFromForm);
                            if ($targetUser && $targetUser->role != 'admin') {
                                $userIdForStockUpdate = $userIdFilterFromForm;
                            }
                        }
                        // Jika $userIdFilterFromForm kosong atau user tidak valid, $userIdForStockUpdate akan tetap null, dan stok tidak akan diupdate untuk user spesifik oleh admin.
                    } else {
                        $userIdForStockUpdate = $currentUser->id;
                    }

                    if ($userIdForStockUpdate) {
                        UserProductStock::updateOrCreate(
                            ['user_id' => $userIdForStockUpdate, 'product_id' => $product->id],
                            ['stock' => $validated['stock']]
                        );
                    } elseif ($currentUser->role == 'admin' && array_key_exists('stock', $validated) && !empty($request->input('user_id_filter'))) {
                        // Log jika admin mencoba update stok untuk user_id_filter yang ada di form,
                        // tapi $userIdForStockUpdate tidak berhasil diset (misal, user_id_filter milik admin sendiri atau tidak valid)
                        Log::warning("Admin (ID: {$currentUser->id}) mencoba update stok untuk produk (ID: {$product->id}) dengan user_id_filter '{$request->input('user_id_filter')}' dari form, namun target user tidak valid atau merupakan admin.");

                    }
                }
            });

            return redirect()->route('dashboard', array_filter($redirectParams))
                ->with('success_message_product', 'Produk berhasil diperbarui.');
        } catch (\Exception $e) {
            Log::error('Update error: ' . $e->getMessage());

            return redirect()->route('dashboard', array_filter($redirectParams))
                ->with('error_message_product', 'Gagal memperbarui produk.')
                ->withInput()
                ->with('open_edit_product_modal', $product->id);
        }
    }


    /**
     * Delete product
     */
    public function destroy(Product $product, Request $request)
    {
        try {
            DB::transaction(function () use ($product) {
                $product->userStocks()->delete();
                $product->delete();
            });

            $redirectParams = [
                'user_id_filter' => $request->query('user_id_filter'),
                'event_id_filter' => $request->query('event_id_filter'),
                'rack_id_filter' => $request->query('rack_id_filter'), // Tambahkan ini
                'search_product' => $request->query('search_product'), // Ambil dari query string
                'page' => $request->query('page') // Ambil dari query string
            ];
            return redirect()->route('dashboard', array_filter($redirectParams))
                ->with('success_message_product', 'Produk berhasil dihapus.');
        } catch (\Exception $e) {
            Log::error('Delete error: ' . $e->getMessage());
            // Gunakan $request->query() untuk mengambil semua parameter query string saat ini
            return redirect()->route('dashboard', array_filter($request->query()))
                ->with('error_message_product', 'Gagal menghapus produk.');
        }
    }
}

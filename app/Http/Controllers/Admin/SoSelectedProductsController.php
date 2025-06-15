<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Rack;
use App\Models\Product;
use App\Models\SoSelectedProduct;
use App\Models\StockOpnameEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SoSelectedProductsController extends Controller
{
    public function store(Request $request, StockOpnameEvent $so_event)
    {
        $selectionMode = $request->input('selection_mode', 'product');

        try {
            if ($selectionMode === 'product') {
                $request->validate([
                    'product_identifier' => 'required|string',
                ], [
                    'product_identifier.required' => 'Produk harus dipilih atau dimasukkan (Kode/Barcode).',
                ]);

                $productIdentifier = $request->input('product_identifier');

                // Find product by product_code or barcode
                $product = Product::where('product_code', $productIdentifier)
                    ->orWhere('barcode', $productIdentifier)
                    ->first();

                if (!$product) {
                    return redirect()
                        ->route('admin.so-events.show', $so_event->id)
                        ->with('error_message_product', 'Produk Tidak Ditemukan. Produk dengan kode/barcode ' . $productIdentifier . ' tidak ditemukan dalam database.')
                        ->withInput();
                }

                // Check if this product is already selected for this SO event
                $existingSelectedProduct = SoSelectedProduct::where('stock_opname_event_id', $so_event->id)
                    ->where('product_id', $product->id)
                    ->exists();

                if ($existingSelectedProduct) {
                    return redirect()
                        ->route('admin.so-events.show', $so_event->id)
                        ->with('error_message_product', 'Item ' . $product->name . ' sudah ada dalam daftar SO Pilihan untuk event ini.')
                        ->withInput();
                }

                SoSelectedProduct::create([
                    'stock_opname_event_id' => $so_event->id,
                    'product_id' => $product->id,
                    'added_by_user_id' => Auth::id(),
                ]);

                return redirect()
                    ->route('admin.so-events.show', $so_event->id)
                    ->with('success_message_product', 'Item ' . $product->name . ' berhasil ditambahkan ke daftar SO Pilihan.');
            } elseif ($selectionMode === 'shelf') {
                $request->validate([
                    'shelf_identifier' => 'required|string', // Bisa juga 'exists:racks,shelf_code' atau 'exists:racks,id'
                ], [
                    'shelf_identifier.required' => 'Rak harus dipilih atau dimasukkan (Kode Rak).',
                ]);

                $shelfIdentifier = $request->input('shelf_identifier');
                // Search for the rack by its name, as the 'code' column does not exist in the 'racks' table.
                $shelf = Rack::where('name', $shelfIdentifier)
                             ->first();

                if (!$shelf) {
                    return redirect()
                        ->route('admin.so-events.show', $so_event->id)
                        ->with('error_message_product', 'Rak Tidak Ditemukan. Rak dengan pengenal (kode/nama) "' . $shelfIdentifier . '" tidak ditemukan.')
                        ->withInput();
                }

                $productsOnShelf = $shelf->products; // Asumsi relasi 'products' ada di model Rack

                if ($productsOnShelf->isEmpty()) {
                    return redirect()
                        ->route('admin.so-events.show', $so_event->id)
                        ->with('warning_message_product', 'Tidak ada produk di rak "' . $shelf->name . '". Tidak ada produk yang ditambahkan.')
                        ->withInput();
                }

                $addedCount = 0;
                $skippedCount = 0;
                $skippedProducts = [];

                foreach ($productsOnShelf as $product) {
                    $created = SoSelectedProduct::firstOrCreate(
                        ['stock_opname_event_id' => $so_event->id, 'product_id' => $product->id],
                        ['added_by_user_id' => Auth::id()]
                    );
                    if ($created->wasRecentlyCreated) {
                        $addedCount++;
                    } else {
                        $skippedCount++;
                        $skippedProducts[] = $product->name;
                    }
                }

                $message = $addedCount . ' produk dari rak "' . $shelf->name . '" berhasil ditambahkan.';
                if ($skippedCount > 0) {
                    $message .= ' ' . $skippedCount . ' produk sudah ada sebelumnya: ' . implode(', ', $skippedProducts) . '.';
                }

                return redirect()
                    ->route('admin.so-events.show', $so_event->id)
                    ->with('success_message_product', $message);
            } else {
                return redirect()
                    ->route('admin.so-events.show', $so_event->id)
                    ->with('error_message_product', 'Mode pemilihan tidak valid.')
                    ->withInput();
            }
        } catch (QueryException $e) {
            Log::error('QueryException in SoSelectedProductsController@store: ' . $e->getMessage(), [
                'sql' => method_exists($e, 'getSql') ? $e->getSql() : 'N/A',
                'bindings' => method_exists($e, 'getBindings') ? $e->getBindings() : 'N/A',
                'request_data' => $request->all(),
                'so_event_id' => $so_event->id,
                'selection_mode' => $selectionMode,
                // 'trace' => $e->getTraceAsString() // Uncomment for very detailed debugging if needed
            ]);

            $errorMessage = 'Error Sistem: Gagal menambahkan produk. Silakan coba lagi.';
            // Optionally, show more details in the flash message during development
            if (config('app.debug')) {
                $errorMessage .= ' (Detail: ' . Str::limit($e->getMessage(), 150) . ')';
            }

            return redirect()
                ->route('admin.so-events.show', $so_event->id)
                ->with('error_message_product', $errorMessage)
                ->withInput();
        }
    }

    public function destroy(SoSelectedProduct $soSelectedProduct)
    {
        $stockOpnameEventId = $soSelectedProduct->stock_opname_event_id;
        $productName = $soSelectedProduct->product->name ?? 'Produk';

        try {
            $soSelectedProduct->delete();
            return redirect()
                ->route('admin.so-events.show', $stockOpnameEventId)
                ->with('success_message_product', 'Item ' . $productName . ' berhasil dihapus dari daftar SO Pilihan.');
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.so-events.show', $stockOpnameEventId)
                ->with('error_message_product', 'Gagal Menghapus. Item ' . $productName . ' gagal dihapus. Silakan coba lagi.');
        }
    }

    /**
     * Remove multiple selected products from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'selected_ids'   => 'required|array',
            'selected_ids.*' => 'exists:so_selected_products,id',
        ]);

        // Ambil event_id dari pertama item untuk redirect (asumsi semua produk dalam event yang sama)
        $firstProduct = SoSelectedProduct::find($request->selected_ids[0]);
        $stockOpnameEventId = $firstProduct->stock_opname_event_id;

        try {
            $deletedCount = SoSelectedProduct::whereIn('id', $request->selected_ids)->delete();

            return redirect()
                ->route('admin.so-events.show', $stockOpnameEventId)
                ->with('success_message_product', 'Berhasil menghapus ' . $deletedCount . ' item dari daftar SO Pilihan.');
        } catch (\Exception $e) {
            Log::error('Bulk delete error: ' . $e->getMessage());

            return redirect()
                ->route('admin.so-events.show', $stockOpnameEventId)
                ->with('error_message_product', 'Gagal menghapus item terpilih. Silakan coba lagi.');
        }
    }
}

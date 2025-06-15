<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Rack; // Tambahkan ini
use App\Models\StockOpnameEvent;
use App\Models\Product; // Untuk dropdown produk
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StockOpnameEventController extends Controller
{
    public function index(Request $request) // Tambahkan Request $request
    {
        $perPage = $request->input('per_page', 5); // Default to 5 items per page
        // Validate that perPage is one of the allowed values
        if (!is_numeric($perPage) || !in_array(intval($perPage), [5, 25, 50, 100])) {
            $perPage = 5; // Fallback to default (5) if invalid value
        } else {
            $perPage = intval($perPage); // Ensure it's an integer
        }
        $searchEventName = $request->input('search_event_name');

        $query = StockOpnameEvent::with('createdBy')->latest();

        if ($searchEventName) {
            $query->where('name', 'like', '%' . $searchEventName . '%');
        }
        // Gunakan variabel $perPage yang sudah divalidasi
        $soEvents = $query->paginate($perPage)->appends($request->except('page')); 
        return view('admin.so_events.index', compact('soEvents', 'searchEventName', 'perPage'));

   }

    public function create()
    {
        return view('admin.so_events.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:stock_opname_events,name',
            'description' => 'nullable|string',
            'status' => 'required|in:pending,active,completed,cancelled',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        StockOpnameEvent::create($request->all() + ['created_by_user_id' => Auth::id()]);

        return redirect()->route('admin.so-events.index')->with('success_message', 'SO Event berhasil dibuat.');
    }

    public function show(Request $request, StockOpnameEvent $so_event)
    {
        $perPageSelected = $request->input('per_page', 5); // Default to 5 items per page
        // Validate that perPageSelected is one of the allowed values
        if (!is_numeric($perPageSelected) || !in_array(intval($perPageSelected), [5, 25, 50, 100])) {
            $perPageSelected = 5; // Fallback to default (5) if invalid value
        } else {
            $perPageSelected = intval($perPageSelected); // Ensure it's an integer
        }

        $availableProductsForModal = Product::orderBy('name')->get(); // Ubah nama variabel di sini
        $availableShelvesForModal = Rack::orderBy('name')->get(); // Tambahkan ini
        $selectedProducts = $so_event->selectedProducts()->with('product', 'addedBy')->latest()->paginate($perPageSelected)->appends($request->except('page'));

        // Menggunakan view yang sudah ada untuk menampilkan produk terpilih,
        // namun sekarang dengan konteks $so_event
        return view('admin.so_selected_products.index', compact('so_event', 'availableProductsForModal', 'availableShelvesForModal', 'selectedProducts', 'perPageSelected'));
    }

    public function edit(StockOpnameEvent $so_event)
    {
        return view('admin.so_events.edit', compact('so_event'));
    }

    public function update(Request $request, StockOpnameEvent $so_event)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:stock_opname_events,name,' . $so_event->id,
            'description' => 'nullable|string',
            'status' => 'required|in:pending,active,completed,cancelled',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $so_event->update($request->all());
            return redirect()->route('admin.so-events.index')->with('success_message', 'SO Event berhasil diperbarui.');
    }

    public function destroy(StockOpnameEvent $so_event)
    {
        $so_event->delete();
            return redirect()->route('admin.so-events.index')->with('success_message', 'SO Event berhasil dihapus.');
    }
}

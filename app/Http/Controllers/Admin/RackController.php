<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Rack;
use Illuminate\Http\Request;

class RackController extends Controller
{
    public function index()
    {
        // Eager load relasi 'products' untuk setiap rak
        $racks = Rack::with('products')->latest()->paginate(10);
        return view('admin.racks.index', compact('racks'));
    }

    public function create()
    {
        return view('admin.racks.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:racks,name',
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        Rack::create($request->all());

        return redirect()->route('admin.racks.index')
                         ->with('success_message', 'Rak berhasil ditambahkan.');
    }

    public function show(Rack $rack)
    {
        // Untuk manajemen CRUD sederhana, halaman 'show' seringkali tidak diperlukan
        // karena detail bisa dilihat di halaman 'edit' atau langsung di 'index'.
        // Jika Anda memiliki halaman detail khusus, gunakan ini:
        // return view('admin.racks.show', compact('rack'));
        // Jika tidak, lebih baik redirect ke edit atau index:
        return redirect()->route('admin.racks.edit', $rack);
    }

    public function edit(Rack $rack)
    {
        return view('admin.racks.edit', compact('rack'));
    }

    public function update(Request $request, Rack $rack)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:racks,name,' . $rack->id,
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $rack->update($request->all());

        return redirect()->route('admin.racks.index')
                         ->with('success_message', 'Rak berhasil diperbarui.');
    }

    public function destroy(Rack $rack)
    {
        try {
            // Check if the rack has any associated products
            if ($rack->products()->exists()) {
                return redirect()->route('admin.racks.index')
                                 ->with('error_message', "Rak '{$rack->name}' tidak dapat dihapus karena masih memiliki produk terkait.");
            }

            $rackName = $rack->name;
            $rack->delete();

            return redirect()->route('admin.racks.index')
                             ->with('success_message', "Rak '{$rackName}' berhasil dihapus.");
        } catch (\Illuminate\Database\QueryException $e) {
            // Menangani error jika ada constraint foreign key yang mencegah penghapusan
            return redirect()->route('admin.racks.index')
                             ->with('error_message', 'Rak tidak dapat dihapus karena mungkin masih terkait dengan data lain.');
        } catch (\Exception $e) {
            return redirect()->route('admin.racks.index')
                             ->with('error_message', 'Terjadi kesalahan saat menghapus rak.');
        }
    }
}

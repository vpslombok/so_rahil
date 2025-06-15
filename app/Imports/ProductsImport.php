<?php

namespace App\Imports;
use App\Models\Rack; // Tambahkan ini

use App\Models\Product;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class ProductsImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnError, SkipsEmptyRows
{
    use SkipsErrors;

    public function model(array $row)
    {
        $barcode = $row['barcode'] ?? null;
        $productCode = $row['kode_produk'] ?? null;
        $rackName = $row['nama_rak'] ?? null; // Ambil nama rak

        if (!$barcode || !$productCode) {
            return null; // Skip jika field utama tidak lengkap
        }

        $existing = Product::where('barcode', $barcode)
            ->where('product_code', $productCode)
            ->first();

        $rackId = null;
        if (!empty($rackName)) {
            $rack = Rack::firstOrCreate(
                ['name' => $rackName],
                ['location' => 'Default Location From Import'] // Anda bisa menambahkan default value lain jika perlu
            );
            $rackId = $rack->id;
        }

        if ($existing) {
            // Update jika sudah ada
            $existing->update([
                'name' => $row['nama_produk'] ?? $existing->name,
                'price' => isset($row['price']) ? floatval($row['price']) : $existing->price,
                'rack_id' => $rackId ?? $existing->rack_id, // Update rack_id jika ada, jika tidak, pertahankan yang lama
            ]);
            return null; // Tidak perlu return model baru, karena sudah update
        }

        // Buat baru
        return new Product([
            'barcode'      => $barcode,
            'product_code' => $productCode,
            'name'         => $row['nama_produk'] ?? null,
            'price'        => isset($row['price']) ? floatval($row['price']) : 0,
            'rack_id'      => $rackId, // Set rack_id untuk produk baru
        ]);
    }

    public function rules(): array
    {
        return [
            'barcode' => ['required', 'max:50'],
            'kode_produk' => ['required', 'max:50'],
            'nama_produk' => ['required', 'max:100'],
            // Deskripsi sudah dihilangkan sebelumnya
            'price' => ['nullable', 'numeric', 'min:0'],
            'nama_rak' => ['nullable', 'string', 'max:255'], // Tambahkan validasi untuk nama_rak
        ];
    }
}

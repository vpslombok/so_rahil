<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Exception;
use PDO;

class LegacyImportController extends Controller
{
    public function importProducts(Request $request)
    {
        error_reporting(E_ALL & ~E_DEPRECATED);

        $config = require __DIR__ . '/../../../config.php';

        if (!Auth::check()) {
            return redirect()->route('login')->with('error_message_product', 'Anda harus login terlebih dahulu.');
        }

        if ($request->isMethod('post')) {
            if ($request->hasFile('file') && $request->file('file')->isValid()) {
                $allowed_extensions = ['xlsx', 'xls'];
                $current_user_id = Auth::id();
                $file = $request->file('file');
                $file_extension = strtolower($file->getClientOriginalExtension());

                if (in_array($file_extension, $allowed_extensions)) {
                    $file_path = $file->getRealPath();

                    try {
                        DB::beginTransaction();

                        $spreadsheet = IOFactory::load($file_path);
                        $worksheet = $spreadsheet->getActiveSheet();
                        $highestRow = $worksheet->getHighestDataRow();
                        $rows = $worksheet->rangeToArray('A1:' . $worksheet->getHighestDataColumn() . $highestRow, null, true, true, true);

                        if (count($rows) < 2) {
                            throw new Exception("File Excel kosong atau tidak memiliki data.");
                        }

                        $headerRow = $rows[1];
                        $header = array_map('strtolower', array_map('trim', array_map(fn($value) => is_scalar($value) ? strval($value) : '', $headerRow)));
                        array_shift($rows);

                        $required_columns = ['no', 'kode_produk', 'barcode', 'nama_produk'];
                        if (count(array_intersect($header, $required_columns)) < count($required_columns)) {
                            throw new Exception("File Excel harus memiliki kolom: " . implode(', ', $required_columns));
                        }

                        $total_rows = 0;
                        $inserted = 0;
                        $updated = 0;
                        $skipped = 0;
                        $errors = [];

                        foreach ($rows as $row_number => $row_data_assoc) {
                            $total_rows++;
                            $row = array_values($row_data_assoc);
                            $row = array_map(fn($v) => is_null($v) ? null : trim(strval($v)), $row);

                            if (empty(array_filter($row, fn($v) => $v !== '' && $v !== null))) {
                                $skipped++;
                                continue;
                            }

                            try {
                                $no = $row[0] ?? null;
                                $product_code = isset($row[1]) && $row[1] !== '' ? (is_numeric($row[1]) ? (string)(int)$row[1] : $row[1]) : null;
                                $barcode = isset($row[2]) && $row[2] !== '' ? (is_numeric($row[2]) ? (string)(int)$row[2] : $row[2]) : null;
                                $name = $row[3] ?? null;
                                $price_from_excel = isset($row[4]) && is_numeric($row[4]) ? (float)$row[4] : 0; // Adjusted index
                                $stock_from_excel = isset($row[5]) && is_numeric($row[5]) ? (int)$row[5] : 0; // Adjusted index

                                if (empty($barcode)) {
                                    throw new Exception("Kolom 'barcode' kosong.");
                                }
                                if (empty($name)) {
                                    throw new Exception("Kolom 'nama_produk' kosong.");
                                }

                                // Cek apakah kombinasi product_code dan barcode sudah ada
                                $existing_product = DB::table('products')
                                    ->where('product_code', $product_code)
                                    ->where('barcode', $barcode)
                                    ->first();

                                // Validasi barcode unik kecuali jika sedang update produk itu sendiri
                                $barcodeUsed = DB::table('products')
                                    ->where('barcode', $barcode)
                                    ->when($existing_product, function ($query) use ($existing_product) {
                                        return $query->where('id', '!=', $existing_product->id);
                                    })
                                    ->exists();

                                if ($barcodeUsed) {
                                    throw new Exception("Kolom 'barcode': '$barcode' sudah digunakan oleh produk lain.");
                                }

                                // Validasi product_code unik kecuali jika sedang update produk itu sendiri
                                if (!empty($product_code)) {
                                    $productCodeUsed = DB::table('products')
                                        ->where('product_code', $product_code)
                                        ->when($existing_product, function ($query) use ($existing_product) {
                                            return $query->where('id', '!=', $existing_product->id);
                                        })
                                        ->exists();

                                    if ($productCodeUsed) {
                                        throw new Exception("Kolom 'kode_produk': '$product_code' sudah digunakan oleh produk lain.");
                                    }
                                }

                                if ($existing_product) {
                                    DB::table('products')
                                        ->where('id', $existing_product->id)
                                        ->update([
                                            'name' => $name,
                                            'price' => $price_from_excel,
                                            'updated_at' => now(),
                                        ]);
                                    $product_id_to_use = $existing_product->id;
                                    $updated++;
                                } else {
                                    if (empty($product_code)) {
                                        $product_code = $this->generateUniqueProductCode();
                                    }

                                    $product_id_to_use = DB::table('products')->insertGetId([
                                        'product_code' => $product_code,
                                        'barcode' => $barcode,
                                        'name' => $name,
                                        'price' => $price_from_excel,
                                        'created_at' => now(),
                                        'updated_at' => now(),
                                    ]);
                                    $inserted++;
                                }

                                if ($product_id_to_use) {
                                    DB::table('user_product_stock')->updateOrInsert(
                                        ['user_id' => $current_user_id, 'product_id' => $product_id_to_use],
                                        ['stock' => $stock_from_excel, 'updated_at' => now()]
                                    );
                                }
                            } catch (Exception $e) {
                                $errors[] = "Baris " . ($row_number + 2) . ", " . $e->getMessage();
                                $skipped++;
                                continue;
                            }
                        }

                        DB::commit();

                        $success_message = "Import berhasil!<br>
                        Total data diproses: $total_rows<br>
                        Data baru ditambahkan: $inserted<br>
                        Data diperbarui: $updated<br>
                        Data dilewati: $skipped";

                        if (!empty($errors)) {
                            $success_message .= "<br><br>Detail error:<br>" . implode("<br>", $errors);
                        }

                        return redirect()->route('dashboard')->with('success_message_product', $success_message);
                    } catch (Exception $e) {
                        DB::rollBack();
                        return redirect()->route('dashboard')->with('error_message_product', "Error: " . $e->getMessage());
                    }
                } else {
                    return redirect()->route('dashboard')->with('error_message_product', "Hanya file Excel (.xlsx, .xls) yang diizinkan.");
                }
            } else {
                return redirect()->route('dashboard')->with('error_message_product', "Terjadi kesalahan saat mengunggah file.");
            }
        }

        return view('import_products_legacy');
    }

    private function generateUniqueProductCode()
    {
        $max_attempts = 100;
        for ($i = 0; $i < $max_attempts; $i++) {
            $length = rand(4, 6);
            $code = '';
            for ($j = 0; $j < $length; $j++) {
                $code .= rand(0, 9);
            }

            if (!DB::table('products')->where('product_code', $code)->exists()) {
                return $code;
            }
        }
        throw new Exception("Gagal membuat kode produk unik setelah $max_attempts percobaan.");
    }
}

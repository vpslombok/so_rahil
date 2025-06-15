<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    // Nama tabel jika tidak sesuai dengan konvensi (plural dari nama model)
    // protected $table = 'products';

    // Kolom yang bisa diisi secara massal (mass assignable)
    protected $fillable = [
        'barcode',
        'product_code',
        'name',
        'price',
        'rack_id', // Add rack_id here
        'last_stock_check',
    ];

    // Jika Anda tidak menggunakan created_at dan updated_at di migrasi
    // public $timestamps = false;

    // Jika last_stock_check adalah timestamp
    protected $casts = [
        'last_stock_check' => 'datetime',
    ];

    /**
     * Mendapatkan data stok produk untuk banyak pengguna.
     * Atau bisa juga relasi ke tabel pivot user_product_stock secara langsung.
     */
    public function userStocks()
    {
        return $this->hasMany(UserProductStock::class);
    }

    /**
     * Get the rack that the product belongs to.
     */
    public function rack()
    {
        return $this->belongsTo(Rack::class);
    }
}

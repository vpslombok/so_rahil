<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserProductStock extends Model
{
    use HasFactory;

    protected $table = 'user_product_stock'; // Pastikan nama tabel benar

    protected $fillable = [
        'user_id',
        'product_id',
        'stock_opname_event_id', // Tambahkan ini agar bisa diisi melalui updateOrCreate
        'stock',
    ];

    // Jika Anda tidak menggunakan created_at dan updated_at di tabel ini
    // public $timestamps = false;

    // Relasi ke User dan Product (opsional, tapi berguna)
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    public function stockOpnameEvent()
    {
        return $this->belongsTo(StockOpnameEvent::class, 'stock_opname_event_id');
    }
}

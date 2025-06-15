<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockCheck extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'stock_checks'; // Sesuaikan jika nama tabel Anda berbeda

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id',
        'user_id',
        'stok_fisik',
        'catatan',
        'tanggal_so',
        'stock_opname_event_id',
        // tambahkan kolom lain yang relevan jika ada, misalnya 'lokasi_id'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tanggal_so' => 'datetime',
    ];

    /**
     * Get the product that owns the stock check.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the user who performed the stock check.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the stock opname event associated with the stock check.
     */
    public function stockOpnameEvent()
    {
        return $this->belongsTo(StockOpnameEvent::class);
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TempStockEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'stock_opname_event_id', // Pastikan ini ada
        'nomor_nota',
        'product_id',
        'product_name',
        'barcode',
        'product_code',
        'system_stock',
        'physical_stock',
        'difference',
    ];

    // Relasi jika ada (misalnya ke User, Product, StockOpnameEvent)
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
     /**
     * Get the UserProductStock record that corresponds to this temporary entry's system stock.
     *
     * This relationship uses a composite key (user_id, product_id) and is primarily
     * designed for lazy loading (e.g., $tempEntry->userProductStock).
     * Eager loading this type of composite key relationship with `with('userProductStock')`
     * can be complex in vanilla Eloquent and might require custom solutions or dedicated packages
     * if high performance on large datasets is needed.
     *
     * Note: The `system_stock` field on this TempStockEntry model already holds the actual stock value
     * from UserProductStock at the time this entry was created. This relationship provides access
     * to the full UserProductStock model instance if needed.
     */
    public function userProductStock()
    {
        return $this->belongsTo(UserProductStock::class, 'user_id', 'user_id')
                    ->where(UserProductStock::query()->qualifyColumn('product_id'), $this->product_id);
    }
}

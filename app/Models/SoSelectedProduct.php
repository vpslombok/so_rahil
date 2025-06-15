<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SoSelectedProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_opname_event_id', // Add this
        'product_id',
        'added_by_user_id',
    ];

    /**
     * Get the product associated with the selected SO product.
     */
    public function stockOpnameEvent() // Add this relationship
    {
        return $this->belongsTo(StockOpnameEvent::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class); // Asumsi model Product ada di App\Models\Product
    }

    /**
     * Get the user who added this product to the SO list.
     */
    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by_user_id'); // Asumsi model User ada di App\Models\User
    }
}

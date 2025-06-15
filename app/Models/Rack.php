<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rack extends Model
{
    use HasFactory;

    protected $fillable = [
            'shelf_code',
        'name',
        'location',
        'description',
    ];

    /**
     * Get the products for the rack.
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }
}

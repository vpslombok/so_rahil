<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use App\Models\User; // Explicit import for clarity
use App\Models\SoSelectedProduct; // Explicit import for clarity

class StockOpnameEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'status',
        'created_by_user_id',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function selectedProducts()
    {
        // Relasi ini seharusnya ke SoSelectedProduct untuk mendapatkan produk-produk yang
        // telah dipilih untuk event stock opname ini.
        return $this->hasMany(SoSelectedProduct::class, 'stock_opname_event_id');
    }

    /**
     * Get the Bootstrap badge class corresponding to the event's status.
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function statusBadgeClass(): Attribute
    {
        return Attribute::make(
            get: fn () => match ($this->status) {
                'active' => 'success',    // Green
                'completed' => 'primary',   // Blue
                'pending' => 'warning',   // Yellow
                'cancelled' => 'danger',    // Red
                'counted' => 'info',      // Light Blue (status used in StockCheckController)
                default => 'secondary', // Grey
            }
        );
    }
}

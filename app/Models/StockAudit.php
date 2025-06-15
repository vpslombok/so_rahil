<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockAudit extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'stock_audits';

    /**
     * Indicates if the model should be timestamped.
     * The stock_audits table uses 'checked_at' but not 'created_at' or 'updated_at'.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id',
        'user_id', // Will be added to the migration
        'stock_opname_event_id', // Will be added to the migration
        'nomor_nota',
        'system_stock',
        'physical_stock',
        'difference',
        'notes',
        'checked_by', // This might be a username string, while user_id is the foreign key
        // 'checked_at' is typically handled by the database due to useCurrent()
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'checked_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function stockOpnameEvent()
    {
        return $this->belongsTo(StockOpnameEvent::class);
    }
}

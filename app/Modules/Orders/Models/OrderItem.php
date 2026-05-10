<?php

namespace App\Modules\Orders\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $table = 'order_items';

    protected $fillable = [
        'order_id',
        'product_id',   // Keep ref for analytics
        'variation_id',
        'name',         // Snapshot
        'price',        // Snapshot
        'quantity',
        'notes',
        'status',       // pending, done (for KDS)
        'destination',  // kitchen, bar
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function variation(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Menu\Models\ProductVariation::class, 'variation_id');
    }
}

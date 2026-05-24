<?php

namespace App\Modules\Service\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceOrderItem extends Model
{
    protected $table = 'service_order_items';

    protected $fillable = [
        'service_order_id',
        'service_item_id',
        'name',
        'quantity',
        'unit_price',
        'line_total',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'float',
        'unit_price' => 'float',
        'line_total' => 'float',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(ServiceOrder::class, 'service_order_id');
    }

    public function serviceItem(): BelongsTo
    {
        return $this->belongsTo(ServiceItem::class, 'service_item_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            $item->line_total = $item->quantity * $item->unit_price;
        });

        static::saved(function ($item) {
            if ($item->order) {
                $item->order->recalculateTotal();
            }
        });

        static::deleted(function ($item) {
            if ($item->order) {
                $item->order->recalculateTotal();
            }
        });
    }
}

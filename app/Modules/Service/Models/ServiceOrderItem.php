<?php

namespace App\Modules\Service\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class ServiceOrderItem extends Model
{
    protected $table = 'service_order_items';

    protected $fillable = [
        'service_order_id',
        'service_item_id',
        'name',
        'unit',
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
            $item->ensureOrderIsEditable();
            $item->line_total = $item->quantity * $item->unit_price;
        });

        static::deleting(fn ($item) => $item->ensureOrderIsEditable());

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

    private function ensureOrderIsEditable(): void
    {
        $orderIds = array_filter([$this->service_order_id, $this->getRawOriginal('service_order_id')]);
        foreach (ServiceOrder::whereIn('id', $orderIds)->get() as $order) {
            if ($order->hasLockedAccount()) {
                throw ValidationException::withMessages(['items' => 'Serviciile unei fișe pe cont client finalizate nu mai pot fi modificate.']);
            }
        }
    }
}

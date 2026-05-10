<?php

namespace App\Modules\Orders\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $table = 'orders';

    protected $fillable = [
        'order_number',
        'status', // pending, preparing, ready, delivered, paid, cancelled
        'total',
        'payment_method', // cash, card, online
        'table_number',
        'notes',
    ];

    protected $casts = [
        'total' => 'decimal:2',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function fiscalDetails()
    {
        return $this->hasOne(OrderFiscalDetail::class);
    }
}

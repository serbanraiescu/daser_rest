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
        'staff_id',
        'stock_deducted_at',
    ];

    protected $casts = [
        'total'              => 'decimal:2',
        'stock_deducted_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function fiscalDetails()
    {
        return $this->hasOne(OrderFiscalDetail::class);
    }

    public function waiter()
    {
        return $this->belongsTo(\App\Modules\Staff\Models\StaffMember::class, 'staff_id');
    }

    /**
     * Verifică dacă stocul a fost deja dedus pentru această comandă.
     */
    public function isStockDeducted(): bool
    {
        return $this->stock_deducted_at !== null;
    }
}

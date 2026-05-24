<?php

namespace App\Modules\Service\Models;

use App\Modules\Staff\Models\StaffMember;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceOrder extends Model
{
    protected $table = 'service_orders';

    protected $fillable = [
        'staff_member_id',
        'customer_name',
        'customer_phone',
        'vehicle_number',
        'notes',
        'status',
        'payment_status',
        'total',
        'payment_method',
        'completed_at',
    ];

    protected $casts = [
        'total' => 'float',
        'completed_at' => 'datetime',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(StaffMember::class, 'staff_member_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ServiceOrderItem::class, 'service_order_id');
    }

    /**
     * Recalculates the order's total based on its items.
     */
    public function recalculateTotal(): void
    {
        $newTotal = $this->items()->sum('line_total');
        $this->total = $newTotal;
        $this->saveQuietly();
    }
}

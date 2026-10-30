<?php

namespace App\Modules\Service\Models;

use App\Modules\Staff\Models\StaffMember;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

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
        'service_customer_id',
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

    public function customer(): BelongsTo
    {
        return $this->belongsTo(ServiceCustomer::class, 'service_customer_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(ServiceInvoice::class, 'service_invoice_id');
    }

    public function scopeUninvoiced(Builder $query): Builder
    {
        return $query->where('status', 'completed')->where('payment_method', 'client_account')->whereNull('service_invoice_id');
    }

    public function hasLockedAccount(): bool
    {
        return $this->getRawOriginal('service_invoice_id') !== null
            || ($this->getRawOriginal('status') === 'completed' && $this->getRawOriginal('payment_method') === 'client_account');
    }

    protected static function booted(): void
    {
        static::saving(function (ServiceOrder $order) {
            if ($order->exists && $order->hasLockedAccount() && $order->isDirty([
                'staff_member_id', 'service_customer_id', 'service_invoice_id', 'customer_name', 'customer_phone',
                'vehicle_number', 'status', 'payment_status', 'payment_method', 'total', 'completed_at',
            ])) {
                throw ValidationException::withMessages(['order' => 'Fișa pe cont client este finalizată și nu mai poate fi modificată financiar.']);
            }

            if ($order->payment_method === 'client_account' && ! $order->hasLockedAccount()) {
                $customer = ServiceCustomer::where('is_active', true)->find($order->service_customer_id);
                if (! $customer) {
                    throw ValidationException::withMessages(['service_customer_id' => 'Selectați un client activ din registru.']);
                }
                $order->customer_name = $customer->name;
                $order->customer_phone = $customer->phone;
                $order->payment_status = 'unpaid';
            }
        });

        static::deleting(function (ServiceOrder $order) {
            if ($order->hasLockedAccount()) {
                throw ValidationException::withMessages(['order' => 'Fișa finalizată pe cont client trebuie păstrată în evidență.']);
            }
        });
    }

    /**
     * Recalculates the order's total based on its items.
     */
    public function recalculateTotal(): void
    {
        if ($this->hasLockedAccount()) {
            throw ValidationException::withMessages(['order' => 'Totalul unei fișe pe cont client finalizate nu poate fi modificat.']);
        }
        $newTotal = $this->items()->sum('line_total');
        $this->total = $newTotal;
        $this->saveQuietly();
    }
}

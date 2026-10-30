<?php

namespace App\Modules\Service\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class ServiceInvoice extends Model
{
    protected $fillable = [
        'service_customer_id', 'number', 'amount', 'orders_total', 'issued_on',
        'recorded_at', 'created_by', 'customer_name', 'customer_fiscal_code',
    ];

    protected $casts = [
        'amount' => 'decimal:2', 'orders_total' => 'decimal:2',
        'issued_on' => 'date', 'recorded_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(ServiceCustomer::class, 'service_customer_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(ServiceOrder::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw ValidationException::withMessages(['invoice' => 'Evidența unei facturi înregistrate nu poate fi suprascrisă.']));
        static::deleting(fn () => throw ValidationException::withMessages(['invoice' => 'O factură înregistrată nu poate fi ștearsă din evidență.']));
    }
}

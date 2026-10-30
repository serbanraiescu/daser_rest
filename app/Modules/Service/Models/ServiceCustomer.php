<?php

namespace App\Modules\Service\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class ServiceCustomer extends Model
{
    protected $fillable = ['name', 'fiscal_code', 'phone', 'email', 'address', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function orders(): HasMany
    {
        return $this->hasMany(ServiceOrder::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(ServiceInvoice::class);
    }

    protected static function booted(): void
    {
        static::deleting(function (ServiceCustomer $customer) {
            if ($customer->orders()->exists() || $customer->invoices()->exists()) {
                throw ValidationException::withMessages(['customer' => 'Clientul are istoric și nu poate fi șters. Dezactivați-l pentru a-l ascunde din terminal.']);
            }
        });
    }
}

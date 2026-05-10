<?php

namespace App\Modules\Orders\Models;

use Illuminate\Database\Eloquent\Model;

class OrderFiscalDetail extends Model
{
    protected $fillable = [
        'order_id',
        'company_name',
        'cui',
        'reg_com',
        'address',
        'bank_name',
        'iban',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}

<?php

namespace App\Modules\Staff\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class StaffMember extends Model
{
    protected $table = 'staff_members';

    protected $fillable = [
        'name',
        'pin_code',
        'role', // waiter, kitchen, bar, manager
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $hidden = [
        'pin_code',
    ];

    public function setPinCodeAttribute($value)
    {
        if ($value) {
            $this->attributes['pin_code'] = bcrypt($value);
        }
    }
}

<?php

namespace App\Modules\Tables\Models;

use Illuminate\Database\Eloquent\Model;

class Table extends Model
{
    protected $fillable = [
        'area_id', 'name', 'seats', 'current_pax', 
        'shape', 'x', 'y', 'width', 'height', 'rotation'
    ];

    public function area()
    {
        return $this->belongsTo(Area::class);
    }
}

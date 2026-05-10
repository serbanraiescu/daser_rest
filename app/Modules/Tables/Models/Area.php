<?php

namespace App\Modules\Tables\Models;

use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    protected $fillable = ['name', 'color', 'width', 'height'];

    public function tables()
    {
        return $this->hasMany(Table::class);
    }
}

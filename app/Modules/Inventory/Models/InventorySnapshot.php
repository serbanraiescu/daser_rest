<?php

namespace App\Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventorySnapshot extends Model
{
    protected $table = 'inventory_snapshots';

    protected $fillable = [
        'name',
        'snapshot_date',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'snapshot_date' => 'date',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(InventorySnapshotItem::class);
    }
}

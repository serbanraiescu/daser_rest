<?php

namespace App\Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventorySnapshotItem extends Model
{
    protected $table = 'inventory_snapshot_items';

    protected $fillable = [
        'inventory_snapshot_id',
        'inventory_item_id',
        'system_stock',
        'physical_stock',
        'difference',
        'observations',
    ];

    protected $casts = [
        'system_stock'   => 'decimal:3',
        'physical_stock' => 'decimal:3',
        'difference'     => 'decimal:3',
    ];

    public function snapshot(): BelongsTo
    {
        return $this->belongsTo(InventorySnapshot::class, 'inventory_snapshot_id');
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    /**
     * Calculează și salvează diferența când stocul fizic este completat.
     */
    public function calculateDifference(): void
    {
        if ($this->physical_stock !== null) {
            $this->difference = (float) $this->physical_stock - (float) $this->system_stock;
            $this->save();
        }
    }
}

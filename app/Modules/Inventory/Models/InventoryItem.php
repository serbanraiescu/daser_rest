<?php

namespace App\Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItem extends Model
{
    protected $table = 'inventory_items';

    protected $fillable = [
        'name',
        'sku',
        'unit',
        'current_stock',
        'minimum_stock',
        'track_inventory',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'current_stock'    => 'decimal:3',
        'minimum_stock'    => 'decimal:3',
        'track_inventory'  => 'boolean',
        'is_active'        => 'boolean',
    ];

    /**
     * Toate mișcările de stoc ale acestui produs.
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Ingredient(e) care sunt legate de acest produs de inventar.
     */
    public function ingredients(): HasMany
    {
        return $this->hasMany(\App\Modules\Menu\Models\Ingredient::class);
    }

    /**
     * Determină dacă stocul este sub minimul configurat.
     */
    public function isBelowMinimum(): bool
    {
        if ($this->minimum_stock === null) {
            return false;
        }

        return (float) $this->current_stock <= (float) $this->minimum_stock;
    }

    /**
     * Scurtătură: unitate de afișare cu stocul curent.
     */
    public function getStockLabelAttribute(): string
    {
        return number_format((float) $this->current_stock, 3, '.', '') . ' ' . $this->unit;
    }
}

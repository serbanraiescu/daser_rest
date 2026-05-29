<?php

namespace App\Modules\Menu\Models;

use App\Modules\Inventory\Models\InventoryItem;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Ingredient extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'unit',
        'is_active',
        'track_stock',
        'inventory_item_id',
        'stock_quantity_per_unit',
    ];

    protected $casts = [
        'is_active'               => 'boolean',
        'track_stock'             => 'boolean',
        'stock_quantity_per_unit' => 'decimal:3',
    ];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'ingredient_product')
            ->withPivot('quantity_used')
            ->withTimestamps();
    }

    /**
     * Produsul de inventar asociat acestui ingredient.
     */
    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    /**
     * Verifică dacă ingredientul urmărește stocul și are un inventory item configurat.
     */
    public function hasActiveStockTracking(): bool
    {
        return $this->track_stock && $this->inventory_item_id !== null;
    }
}

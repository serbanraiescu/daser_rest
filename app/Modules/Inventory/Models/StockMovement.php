<?php

namespace App\Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    protected $table = 'stock_movements';

    /**
     * Tipuri permise de mișcări.
     */
    public const TYPE_IN         = 'in';
    public const TYPE_OUT        = 'out';
    public const TYPE_ADJUSTMENT = 'adjustment';
    public const TYPE_WASTE      = 'waste';
    public const TYPE_SALE       = 'sale';

    public const TYPES = [
        self::TYPE_IN         => 'Intrare Marfă',
        self::TYPE_OUT        => 'Ieșire',
        self::TYPE_ADJUSTMENT => 'Ajustare Inventar',
        self::TYPE_WASTE      => 'Pierdere / Deșeu',
        self::TYPE_SALE       => 'Vânzare (Auto)',
    ];

    protected $fillable = [
        'inventory_item_id',
        'type',
        'quantity',
        'stock_before',
        'stock_after',
        'note',
        'reference_type',
        'reference_id',
        'created_by',
    ];

    protected $casts = [
        'quantity'     => 'decimal:3',
        'stock_before' => 'decimal:3',
        'stock_after'  => 'decimal:3',
    ];

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    /**
     * Returnează label-ul uman pentru tip.
     */
    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? ucfirst($this->type);
    }
}

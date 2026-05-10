<?php

namespace App\Modules\Menu\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Modules\Menu\Models\Ingredient;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $table = 'products';

    protected $fillable = [
        'category_id',
        'name',
        'price',
        'description',
        'image',
        'is_active',
        'is_available',
        'sort_order',
        'vat_rate',
        'nutritional_data', // JSON
        'measurement_value',
        'measurement_unit',
    ];

    protected $casts = [
        'measurement_value' => 'decimal:2',
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'is_available' => 'boolean',
        'nutritional_data' => 'array',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
    
    public function ingredients()
    {
        return $this->belongsToMany(Ingredient::class, 'ingredient_product');
    }

    public function variations(): HasMany
    {
        return $this->hasMany(ProductVariation::class);
    }
}

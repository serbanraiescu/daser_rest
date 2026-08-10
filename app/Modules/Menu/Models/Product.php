<?php

namespace App\Modules\Menu\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Modules\Menu\Models\Ingredient;
use App\Modules\Menu\Models\Allergen;
use App\Modules\Menu\Models\ProductIngredient;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model
{
    protected $table = 'products';

    protected $fillable = [
        'category_id',
        'name',
        'name_en',
        'price',
        'description',
        'description_en',
        'image',
        'is_active',
        'is_available',
        'sort_order',
        'vat_rate',
        'nutritional_data', // JSON
        'measurement_value',
        'measurement_unit',
        'is_frozen',
        'frozen_note',
        'allergens', // Legacy column
    ];

    protected $casts = [
        'measurement_value' => 'decimal:2',
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'is_available' => 'boolean',
        'is_frozen' => 'boolean',
        'nutritional_data' => 'array',
    ];

    protected $appends = ['display_name', 'display_description'];

    public function getDisplayNameAttribute(): string
    {
        return $this->translationFor(app()->getLocale())?->name ?: $this->name;
    }

    public function getDisplayDescriptionAttribute(): ?string
    {
        return $this->translationFor(app()->getLocale())?->description ?: $this->description;
    }

    public function translations(): HasMany
    {
        return $this->hasMany(ProductTranslation::class);
    }

    public function translationFor(string $locale): ?ProductTranslation
    {
        return $this->relationLoaded('translations')
            ? $this->translations->firstWhere('locale', $locale)
            : $this->translations()->where('locale', $locale)->first();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
    
    public function ingredients(): BelongsToMany
    {
        return $this->belongsToMany(Ingredient::class, 'ingredient_product')
            ->using(ProductIngredient::class)
            ->withPivot('quantity_used')
            ->withTimestamps();
    }

    public function productIngredients(): HasMany
    {
        return $this->hasMany(ProductIngredient::class, 'product_id');
    }

    public function allergenRelations(): BelongsToMany
    {
        return $this->belongsToMany(Allergen::class, 'allergen_product');
    }

    public function variations(): HasMany
    {
        return $this->hasMany(ProductVariation::class);
    }
}

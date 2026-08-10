<?php

namespace App\Modules\Menu\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $appends = ['display_name'];
    protected $table = 'categories';

    protected $fillable = [
        'menu_id',
        'name',
        'image',
        'is_active',
        'sort_order',
        'destination',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(CategoryTranslation::class);
    }

    public function getDisplayNameAttribute(): string
    {
        $translation = $this->relationLoaded('translations')
            ? $this->translations->firstWhere('locale', app()->getLocale())
            : $this->translations()->where('locale', app()->getLocale())->first();

        return $translation?->name ?: $this->name;
    }

    public function menu()
    {
        return $this->belongsTo(Menu::class);
    }
}

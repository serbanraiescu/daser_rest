<?php

namespace App\Modules\Service\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ServiceCategory extends Model
{
    protected $table = 'service_categories';

    protected $fillable = [
        'name',
        'parent_id',
        'slug',
        'description',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(ServiceItem::class, 'service_category_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($category) {
            if ($category->exists && (int) $category->parent_id === (int) $category->id) {
                $category->parent_id = null;
            }

            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }
}

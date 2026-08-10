<?php

namespace App\Modules\Gallery\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class GalleryAlbum extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'description',
        'event_date',
        'show_on_homepage',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'event_date' => 'date',
        'show_on_homepage' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (GalleryAlbum $album): void {
            if (blank($album->slug) || $album->isDirty('title')) {
                $baseSlug = Str::slug($album->title) ?: 'album';
                $slug = $baseSlug;
                $suffix = 2;

                while (static::query()->where('slug', $slug)->whereKeyNot($album->getKey())->exists()) {
                    $slug = $baseSlug.'-'.$suffix++;
                }

                $album->slug = $slug;
            }
        });
    }

    public function images(): HasMany
    {
        return $this->hasMany(GalleryImage::class)->orderBy('sort_order')->orderBy('id');
    }
}

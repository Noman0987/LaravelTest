<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Cache;

class Translation extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'locale',
        'value',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the tags associated with the translation.
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    /**
     * Boot the model and add event listeners for cache invalidation.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::saved(function ($translation) {
            static::clearCache();
        });

        static::deleted(function ($translation) {
            static::clearCache();
        });
    }

    /**
     * Clear all translation-related caches.
     */
    public static function clearCache(): void
    {
        Cache::forget('translations:all:json');
        Cache::forget('translations:all:json:etag');
        Cache::forget('export:all');
        Cache::forget('export:all:etag');
        
        // Clear locale-specific caches
        $locales = static::distinct()->pluck('locale');
        foreach ($locales as $locale) {
            Cache::forget("export:{$locale}");
            Cache::forget("export:{$locale}:etag");
        }
    }

    /**
     * Scope to filter by locale.
     */
    public function scopeByLocale($query, string $locale)
    {
        return $query->where('locale', $locale);
    }

    /**
     * Scope to filter by key pattern.
     */
    public function scopeByKey($query, string $key)
    {
        return $query->where('key', 'like', $key . '%');
    }

    /**
     * Scope to filter by content.
     */
    public function scopeByContent($query, string $content)
    {
        return $query->where('value', 'like', '%' . $content . '%');
    }

    /**
     * Scope to filter by tags.
     */
    public function scopeByTags($query, array $tags)
    {
        return $query->whereHas('tags', function ($q) use ($tags) {
            $q->whereIn('name', $tags);
        });
    }
}

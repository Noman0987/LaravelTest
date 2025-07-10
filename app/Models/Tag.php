<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Cache;

class Tag extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the translations associated with the tag.
     */
    public function translations(): BelongsToMany
    {
        return $this->belongsToMany(Translation::class);
    }

    /**
     * Boot the model and add event listeners for cache invalidation.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::saved(function ($tag) {
            static::clearCache();
        });

        static::deleted(function ($tag) {
            static::clearCache();
        });
    }

    /**
     * Clear all tag-related caches.
     */
    public static function clearCache(): void
    {
        Cache::forget('tags:all');
        Translation::clearCache();
    }

    /**
     * Scope to filter by name pattern.
     */
    public function scopeByName($query, string $name)
    {
        return $query->where('name', 'like', '%' . $name . '%');
    }

    /**
     * Get all tags with caching.
     */
    public static function getAllCached()
    {
        return Cache::remember('tags:all', 3600, function () {
            return static::select('id', 'name')
                ->orderBy('name')
                ->get();
        });
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class HomeLayout extends Model
{
    protected $fillable = [
        'tenant_id',
        'category_handle',
        'category_gid',
        'category_title',
        'is_default',
        'layout_data',
        'name',
        'status',
    ];

    protected $casts = [
        'layout_data' => 'array',
        'is_default' => 'boolean',
    ];

    protected static function booted(): void
    {
        // Clear cache when layout is created, updated, or deleted
        static::saved(function (HomeLayout $layout) {
            $layout->clearCache();
        });

        static::deleted(function (HomeLayout $layout) {
            $layout->clearCache();
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    protected function clearCache(): void
    {
        // Clear cache for this specific category
        if ($this->category_handle) {
            Cache::forget("home_layout:{$this->tenant_id}:{$this->category_handle}");
        }

        // Clear cache for default layout
        Cache::forget("home_layout:{$this->tenant_id}:default");

        // If this is the default layout, clear all category caches for this tenant
        // since they might fall back to the default
        if ($this->is_default) {
            Cache::flush(); // Or use tags if available: Cache::tags(["tenant_{$this->tenant_id}"])->flush();
        }
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeForCategory($query, string $handle)
    {
        return $query->where('category_handle', $handle);
    }
}

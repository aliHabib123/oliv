<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

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
        // Transform storage paths to full URLs before saving
        static::saving(function (HomeLayout $layout) {
            if ($layout->isDirty('layout_data') && !empty($layout->layout_data)) {
                $layout->layout_data = static::transformStoragePathsToUrls($layout->layout_data);
            }
        });

        // Clear cache when layout is created, updated, or deleted
        static::saved(function (HomeLayout $layout) {
            $layout->clearCache();
        });

        static::deleted(function (HomeLayout $layout) {
            $layout->clearCache();
        });
    }

    /**
     * Transform storage paths to full URLs in layout data
     */
    protected static function transformStoragePathsToUrls(array $data): array
    {
        // Transform banner image to full URL
        if (!empty($data['banner_image']) && !filter_var($data['banner_image'], FILTER_VALIDATE_URL)) {
            $data['banner_image'] = url(Storage::url($data['banner_image']));
        }

        // Transform block images to full URLs
        if (!empty($data['blocks']) && is_array($data['blocks'])) {
            foreach ($data['blocks'] as &$block) {
                // Handle different block types that might have images
                if (!empty($block['image']) && !filter_var($block['image'], FILTER_VALIDATE_URL)) {
                    $block['image'] = url(Storage::url($block['image']));
                }
                if (!empty($block['background_image']) && !filter_var($block['background_image'], FILTER_VALIDATE_URL)) {
                    $block['background_image'] = url(Storage::url($block['background_image']));
                }
                if (!empty($block['icon']) && !filter_var($block['icon'], FILTER_VALIDATE_URL)) {
                    $block['icon'] = url(Storage::url($block['icon']));
                }
                if (!empty($block['image_url']) && !filter_var($block['image_url'], FILTER_VALIDATE_URL)) {
                    $block['image_url'] = url(Storage::url($block['image_url']));
                }

                // Handle items array if present (for grids, lists, etc.)
                if (!empty($block['items']) && is_array($block['items'])) {
                    foreach ($block['items'] as &$item) {
                        if (!empty($item['image']) && !filter_var($item['image'], FILTER_VALIDATE_URL)) {
                            $item['image'] = url(Storage::url($item['image']));
                        }
                        if (!empty($item['image_url']) && !filter_var($item['image_url'], FILTER_VALIDATE_URL)) {
                            $item['image_url'] = url(Storage::url($item['image_url']));
                        }
                    }
                    unset($item);
                }
            }
            unset($block);
        }

        return $data;
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

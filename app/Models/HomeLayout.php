<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
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

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Country extends Model
{
    protected $fillable = [
        'code',
        'name',
        'currency',
        'symbol',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function packagePricing(): HasOne
    {
        return $this->hasOne(PackagePricing::class, 'country_code', 'code');
    }

    public function addonPricing(): HasMany
    {
        return $this->hasMany(AddonPricing::class, 'country_code', 'code');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'country_code', 'code');
    }

    /**
     * Format price with country symbol
     */
    public function formatPrice(float $amount): string
    {
        return $this->symbol . number_format($amount, 2);
    }

    /**
     * Scope for active countries only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for ordering by sort order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}

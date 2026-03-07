<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Package extends Model
{
    protected $fillable = [
        'name',
        'description',
        'price',
        'features',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'features' => 'array',
        'is_active' => 'boolean',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Get pricing for specific country
     */
    public function getPricingForCountry(string $countryCode): ?PackagePricing
    {
        return PackagePricing::where('country_code', $countryCode)
            ->where('is_active', true)
            ->first();
    }
}

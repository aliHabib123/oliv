<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Addon extends Model
{
    protected $fillable = [
        'name',
        'subtitle',
        'key',
        'description',
        'image',
        'video',
        'price',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tenantAddons(): HasMany
    {
        return $this->hasMany(TenantAddon::class);
    }

    public function countryPricing(): HasMany
    {
        return $this->hasMany(AddonPricing::class);
    }

    /**
     * Get pricing for specific country
     */
    public function getPricingForCountry(string $countryCode): ?AddonPricing
    {
        return $this->countryPricing()
            ->where('country_code', $countryCode)
            ->where('is_active', true)
            ->first();
    }
}

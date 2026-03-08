<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AddonPricing extends Model
{
    protected $table = 'addon_pricing';

    protected $fillable = [
        'addon_id',
        'country_code',
        'monthly_price',
        'yearly_price',
        'stripe_monthly_price_id',
        'stripe_yearly_price_id',
        'is_active',
    ];

    protected $casts = [
        'monthly_price' => 'decimal:2',
        'yearly_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function addon(): BelongsTo
    {
        return $this->belongsTo(Addon::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_code', 'code');
    }

    /**
     * Get formatted monthly price with currency symbol
     */
    public function getFormattedMonthlyPriceAttribute(): string
    {
        return $this->country->formatPrice($this->monthly_price);
    }

    /**
     * Get formatted yearly price with currency symbol
     */
    public function getFormattedYearlyPriceAttribute(): string
    {
        return $this->country->formatPrice($this->yearly_price);
    }

    /**
     * Get price for specific billing period
     */
    public function getPriceForPeriod(string $billingPeriod): float
    {
        return $billingPeriod === 'yearly' ? (float)$this->yearly_price : (float)$this->monthly_price;
    }

    /**
     * Get Stripe price ID for specific billing period
     */
    public function getStripePriceId(string $billingPeriod): ?string
    {
        return $billingPeriod === 'yearly' ? $this->stripe_yearly_price_id : $this->stripe_monthly_price_id;
    }

    /**
     * Scope for active pricing only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

<?php

namespace App\Services;

use App\Models\Addon;
use App\Models\AddonPricing;
use App\Models\Country;
use App\Models\PackagePricing;

class CountryPricingService
{
    /**
     * Get country from session or default to US
     */
    public function getCurrentCountry(): string
    {
        return session('country', 'US');
    }

    /**
     * Set country in session
     */
    public function setCountry(string $countryCode): void
    {
        session(['country' => $countryCode]);
    }

    /**
     * Get all active countries
     */
    public function getActiveCountries()
    {
        return Country::active()->ordered()->get();
    }

    /**
     * Get package pricing for specific country and billing period
     */
    public function getPackagePricing(string $countryCode, string $billingPeriod): ?array
    {
        $pricing = PackagePricing::where('country_code', $countryCode)
            ->where('is_active', true)
            ->with('country')
            ->first();

        if (!$pricing) {
            return null;
        }

        $price = $billingPeriod === 'yearly' ? $pricing->yearly_price : $pricing->monthly_price;
        $stripePriceId = $pricing->getStripePriceId($billingPeriod);

        return [
            'price' => $price,
            'formatted_price' => $pricing->country->formatPrice($price),
            'currency' => $pricing->country->currency,
            'symbol' => $pricing->country->symbol,
            'stripe_price_id' => $stripePriceId,
            'billing_period' => $billingPeriod,
        ];
    }

    /**
     * Get addon pricing for specific country and billing period
     */
    public function getAddonPricing(int $addonId, string $countryCode, string $billingPeriod): ?array
    {
        $pricing = AddonPricing::where('addon_id', $addonId)
            ->where('country_code', $countryCode)
            ->where('is_active', true)
            ->with('country', 'addon')
            ->first();

        if (!$pricing) {
            return null;
        }

        $price = $billingPeriod === 'yearly' ? $pricing->yearly_price : $pricing->monthly_price;
        $stripePriceId = $pricing->getStripePriceId($billingPeriod);

        return [
            'price' => $price,
            'formatted_price' => $pricing->country->formatPrice($price),
            'currency' => $pricing->country->currency,
            'symbol' => $pricing->country->symbol,
            'stripe_price_id' => $stripePriceId,
            'billing_period' => $billingPeriod,
            'addon' => $pricing->addon,
        ];
    }

    /**
     * Get all addons with pricing for specific country
     */
    public function getAddonsWithPricing(string $countryCode, string $billingPeriod = 'monthly')
    {
        $addons = Addon::where('is_active', true)->get();

        return $addons->mapWithKeys(function ($addon) use ($countryCode, $billingPeriod) {
            $pricing = $this->getAddonPricing($addon->id, $countryCode, $billingPeriod);

            return [$addon->id => $pricing];
        })->toArray();
    }

    /**
     * Calculate price with yearly discount
     */
    public function calculateYearlyDiscount(float $monthlyPrice): array
    {
        $yearlyPrice = $monthlyPrice * 12;
        $discountPercentage = 30; // 30% discount for yearly
        $discountedYearlyPrice = $yearlyPrice * (1 - $discountPercentage / 100);

        return [
            'monthly_total' => $yearlyPrice,
            'yearly_price' => $discountedYearlyPrice,
            'discount_amount' => $yearlyPrice - $discountedYearlyPrice,
            'discount_percentage' => $discountPercentage,
        ];
    }

    /**
     * Validate country code
     */
    public function isValidCountry(string $countryCode): bool
    {
        return Country::where('code', $countryCode)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Get country by code
     */
    public function getCountry(string $countryCode): ?Country
    {
        return Country::where('code', $countryCode)
            ->where('is_active', true)
            ->first();
    }
}

<?php

namespace Database\Seeders;

use App\Models\Addon;
use App\Models\AddonPricing;
use App\Models\PackagePricing;
use Illuminate\Database\Seeder;

class PricingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Package pricing for each country
        $packagePricing = [
            'US' => ['monthly' => 12.99, 'yearly' => 109.99, 'setup_fee' => 408],
            'AE' => ['monthly' => 47.65, 'yearly' => 479.99, 'setup_fee' => 1500],
            'SA' => ['monthly' => 48.70, 'yearly' => 487.00, 'setup_fee' => 1532],
            'QA' => ['monthly' => 47.30, 'yearly' => 473.00, 'setup_fee' => 1487],
            'GB' => ['monthly' => 10.25, 'yearly' => 102.50, 'setup_fee' => 304],
            'EU' => ['monthly' => 11.95, 'yearly' => 119.50, 'setup_fee' => 349],
        ];

        foreach ($packagePricing as $countryCode => $prices) {
            PackagePricing::updateOrCreate(
                ['country_code' => $countryCode],
                [
                    'monthly_price' => $prices['monthly'],
                    'yearly_price' => $prices['yearly'],
                    'setup_fee' => $prices['setup_fee'],
                    'is_active' => true,
                ]
            );
        }

        // Addon pricing - get all addons and create pricing for each country
        $addons = Addon::where('is_active', true)->get();

        // Example addon pricing multipliers based on country
        $addonPricing = [
            'US' => ['monthly' => 1.0, 'yearly' => 10.0],      // Base price
            'AE' => ['monthly' => 3.67, 'yearly' => 36.70],    // ~3.67x
            'SA' => ['monthly' => 3.75, 'yearly' => 37.50],    // ~3.75x
            'QA' => ['monthly' => 3.64, 'yearly' => 36.40],    // ~3.64x
            'GB' => ['monthly' => 0.79, 'yearly' => 7.90],     // ~0.79x
            'EU' => ['monthly' => 0.92, 'yearly' => 9.20],     // ~0.92x
        ];

        foreach ($addons as $addon) {
            foreach ($addonPricing as $countryCode => $multipliers) {
                AddonPricing::updateOrCreate(
                    [
                        'addon_id' => $addon->id,
                        'country_code' => $countryCode,
                    ],
                    [
                        'monthly_price' => $addon->price * $multipliers['monthly'],
                        'yearly_price' => $addon->price * $multipliers['yearly'],
                        'is_active' => true,
                    ]
                );
            }
        }

        $this->command->info('Pricing data seeded successfully!');
    }
}

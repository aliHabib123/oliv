<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CountriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $countries = [
            [
                'code' => 'US',
                'name' => 'United States',
                'currency' => 'USD',
                'symbol' => '$',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'code' => 'AE',
                'name' => 'United Arab Emirates',
                'currency' => 'AED',
                'symbol' => 'د.إ',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'code' => 'SA',
                'name' => 'Saudi Arabia',
                'currency' => 'SAR',
                'symbol' => 'ر.س',
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'code' => 'QA',
                'name' => 'Qatar',
                'currency' => 'QAR',
                'symbol' => 'ر.ق',
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'code' => 'GB',
                'name' => 'United Kingdom',
                'currency' => 'GBP',
                'symbol' => '£',
                'is_active' => true,
                'sort_order' => 5,
            ],
            [
                'code' => 'EU',
                'name' => 'Europe',
                'currency' => 'EUR',
                'symbol' => '€',
                'is_active' => true,
                'sort_order' => 6,
            ],
        ];

        foreach ($countries as $country) {
            \App\Models\Country::updateOrCreate(
                ['code' => $country['code']],
                $country
            );
        }
    }
}

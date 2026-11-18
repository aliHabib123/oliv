<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\Package;
use App\Models\PackagePricing;
use App\Services\CountryPricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SetupFeeTest extends TestCase
{
    use RefreshDatabase;

    private PackagePricing $pricing;

    protected function setUp(): void
    {
        parent::setUp();

        Country::create(['code' => 'AE', 'name' => 'United Arab Emirates', 'currency' => 'AED', 'symbol' => 'AED', 'is_active' => true]);

        Package::create([
            'name' => 'One price. Everything included.',
            'description' => 'Design, build, launch and ongoing support.',
            'price' => 1299,
            'features' => ['Native iOS & Android apps' => ''],
            'is_active' => true,
        ]);

        $this->pricing = PackagePricing::create([
            'country_code' => 'AE',
            'monthly_price' => 1299,
            'yearly_price' => 10999,
            'setup_fee' => 1500,
            'is_active' => true,
        ]);
    }

    public function test_package_pricing_includes_setup_fee(): void
    {
        $pricing = app(CountryPricingService::class)->getPackagePricing('AE', 'monthly');

        $this->assertSame(1500.0, $pricing['setup_fee']);
    }

    public function test_setup_fee_line_item_is_one_time(): void
    {
        $service = app(CountryPricingService::class);
        $lineItem = $service->getSetupFeeLineItem($service->getPackagePricing('AE', 'yearly'));

        $this->assertSame('aed', $lineItem['price_data']['currency']);
        $this->assertSame(150000, $lineItem['price_data']['unit_amount']);
        $this->assertArrayNotHasKey('recurring', $lineItem['price_data']);
    }

    public function test_no_setup_fee_line_item_when_fee_is_zero(): void
    {
        $this->pricing->update(['setup_fee' => 0]);
        $service = app(CountryPricingService::class);

        $this->assertNull($service->getSetupFeeLineItem($service->getPackagePricing('AE', 'monthly')));
    }

    public function test_subscription_page_shows_setup_fee_and_terms(): void
    {
        $this->withSession(['country' => 'AE'])
            ->get(route('subscriptions.index'))
            ->assertOk()
            ->assertSee('Plus one-time set-up fee:')
            ->assertSee('AED 1,500')
            ->assertSee('AED 2,799')
            ->assertSee('No commissions on sales. No hidden fees. One simple 12 month term.')
            ->assertSee('After your first 12 months, cancel with 30 days notice.');
    }

    public function test_subscription_page_hides_setup_fee_when_zero(): void
    {
        $this->pricing->update(['setup_fee' => 0]);

        $this->withSession(['country' => 'AE'])
            ->get(route('subscriptions.index'))
            ->assertOk()
            ->assertDontSee('Plus one-time set-up fee:')
            ->assertDontSee('Due today');
    }
}

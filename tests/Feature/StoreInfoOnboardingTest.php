<?php

namespace Tests\Feature;

use App\Http\Controllers\Onboarding\FinishController;
use App\Http\Controllers\Onboarding\StoreInfoController;
use App\Models\AppConfiguration;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreInfoOnboardingTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Test Store',
            'status' => 'active',
            'shopify_shop_domain' => 'mystore.myshopify.com',
            'shopify_admin_token' => 'shpat_x',
        ]);
    }

    public function test_show_requires_signed_url(): void
    {
        $this->get(route('onboarding.store-info.show', $this->tenant))->assertForbidden();
    }

    public function test_show_redirects_to_shopify_when_not_connected(): void
    {
        $this->tenant->update(['shopify_admin_token' => null]);

        $this->get(StoreInfoController::signedShowUrl($this->tenant))
            ->assertRedirectContains('/onboarding/'.$this->tenant->id.'/shopify');
    }

    public function test_show_renders(): void
    {
        $this->get(StoreInfoController::signedShowUrl($this->tenant))
            ->assertOk()
            ->assertSee('Add your store info');
    }

    public function test_save_persists_info_and_completes_onboarding(): void
    {
        $this->post(route('onboarding.store-info.save', $this->tenant), [
            'contact' => ['email' => 'shop@example.com', 'phone' => '+97150000000'],
            'social_media' => ['instagram' => 'https://instagram.com/shop'],
            'static_pages' => ['privacy_policy' => 'https://shop.com/privacy'],
        ])->assertRedirectContains('/onboarding/'.$this->tenant->id.'/finish');

        $config = AppConfiguration::where('tenant_id', $this->tenant->id)->first()->config;
        $this->assertSame('shop@example.com', $config['contact']['email']);
        $this->assertSame('https://instagram.com/shop', $config['social_media']['instagram']);
        $this->assertSame('https://shop.com/privacy', $config['static_pages']['privacy_policy']);
        $this->assertArrayHasKey('completed_at', $config['onboarding']);
    }

    public function test_save_allows_skipping_all_fields(): void
    {
        $this->post(route('onboarding.store-info.save', $this->tenant), [])
            ->assertRedirectContains('/onboarding/'.$this->tenant->id.'/finish');

        $config = AppConfiguration::where('tenant_id', $this->tenant->id)->first()->config;
        $this->assertArrayHasKey('completed_at', $config['onboarding']);
    }

    public function test_save_rejects_invalid_email_and_url(): void
    {
        $this->from(StoreInfoController::signedShowUrl($this->tenant))
            ->post(route('onboarding.store-info.save', $this->tenant), [
                'contact' => ['email' => 'not-an-email'],
                'social_media' => ['facebook' => 'not-a-url'],
            ])
            ->assertSessionHasErrors(['contact.email', 'social_media.facebook']);
    }

    public function test_finish_requires_signed_url(): void
    {
        $this->get(route('onboarding.finish.show', $this->tenant))->assertForbidden();
    }

    public function test_finish_renders_completion(): void
    {
        AppConfiguration::create([
            'tenant_id' => $this->tenant->id,
            'config' => ['basic' => ['app_name' => 'Cela Vogue']],
        ]);

        $this->get(FinishController::signedShowUrl($this->tenant))
            ->assertOk()
            ->assertSee("You're all set", false)
            ->assertSee('Cela Vogue');
    }
}

<?php

namespace Tests\Feature;

use App\Http\Controllers\Onboarding\BrandingController;
use App\Models\AppConfiguration;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BrandingOnboardingTest extends TestCase
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
            'shopify_storefront_token' => 'sft_x',
        ]);
    }

    public function test_show_requires_signed_url(): void
    {
        $this->get(route('onboarding.branding.show', $this->tenant))->assertForbidden();
    }

    public function test_show_redirects_to_shopify_step_when_not_connected(): void
    {
        $this->tenant->update(['shopify_admin_token' => null]);

        $this->get(BrandingController::signedShowUrl($this->tenant))
            ->assertredirectContains('/onboarding/'.$this->tenant->id.'/shopify');
    }

    public function test_show_prefills_from_shopify_brand_assets(): void
    {
        Http::fake([
            '*/api/*/graphql.json' => Http::response([
                'data' => ['shop' => [
                    'name' => 'Cela Vogue',
                    'brand' => [
                        'logo' => ['image' => ['url' => 'https://cdn.shopify.com/logo.png']],
                        'colors' => [
                            'primary' => [['background' => '#112233']],
                            'secondary' => [['background' => '#445566']],
                        ],
                    ],
                ]],
            ]),
        ]);

        $this->get(BrandingController::signedShowUrl($this->tenant))
            ->assertOk()
            ->assertSee('Cela Vogue')
            ->assertSee('#112233')
            ->assertSee('#445566')
            ->assertSee('https://cdn.shopify.com/logo.png');
    }

    public function test_save_requires_app_name(): void
    {
        $this->from(BrandingController::signedShowUrl($this->tenant))
            ->post(route('onboarding.branding.save', $this->tenant), ['app_name' => ''])
            ->assertSessionHasErrors('app_name');
    }

    public function test_save_rejects_invalid_color(): void
    {
        $this->from(BrandingController::signedShowUrl($this->tenant))
            ->post(route('onboarding.branding.save', $this->tenant), [
                'app_name' => 'My App',
                'primary_color' => 'not-a-hex',
            ])
            ->assertSessionHasErrors('primary_color');
    }

    public function test_save_persists_branding_to_app_configuration(): void
    {
        Storage::fake('public');

        $this->post(route('onboarding.branding.save', $this->tenant), [
            'app_name' => 'My App',
            'primary_color' => '#aabbcc',
            'secondary_color' => '#ddeeff',
        ])->assertRedirectContains('/onboarding/'.$this->tenant->id.'/categories');

        $config = AppConfiguration::where('tenant_id', $this->tenant->id)->first();
        $this->assertSame('My App', $config->config['basic']['app_name']);
        $this->assertSame('#aabbcc', $config->config['basic']['primary_color']);
        $this->assertSame('#ddeeff', $config->config['basic']['secondary_color']);
    }

    public function test_save_stores_uploaded_logo(): void
    {
        Storage::fake('public');

        $this->post(route('onboarding.branding.save', $this->tenant), [
            'app_name' => 'My App',
            'logo' => UploadedFile::fake()->image('logo.png', 200, 200),
        ])->assertRedirectContains('/onboarding/'.$this->tenant->id.'/categories');

        $config = AppConfiguration::where('tenant_id', $this->tenant->id)->first();
        $logoPath = $config->config['basic']['logo'];
        $this->assertNotEmpty($logoPath);
        Storage::disk('public')->assertExists($logoPath);
    }

    public function test_save_downloads_suggested_logo_when_no_upload(): void
    {
        Storage::fake('public');
        Http::fake([
            'https://cdn.shopify.com/logo.png' => Http::response('binary-image-bytes', 200),
        ]);

        $this->post(route('onboarding.branding.save', $this->tenant), [
            'app_name' => 'My App',
            'suggested_logo_url' => 'https://cdn.shopify.com/logo.png',
        ])->assertRedirectContains('/onboarding/'.$this->tenant->id.'/categories');

        $config = AppConfiguration::where('tenant_id', $this->tenant->id)->first();
        $logoPath = $config->config['basic']['logo'];
        $this->assertStringEndsWith('.png', $logoPath);
        Storage::disk('public')->assertExists($logoPath);
    }
}

<?php

namespace Tests\Feature;

use App\Http\Controllers\Onboarding\ShopifyOnboardingController;
use App\Models\Tenant;
use App\Services\Shopify\ShopifyAdminTokenService;
use App\Services\Shopify\ShopifyOAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ShopifyOnboardingTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.shopify.client_id', 'test-client-id');
        config()->set('services.shopify.client_secret', 'test-secret');
        config()->set('services.shopify.api_version', '2025-10');
        config()->set('services.shopify.scopes', 'read_products,write_orders');

        $this->tenant = Tenant::create([
            'name' => 'Test Store',
            'status' => 'active',
        ]);
    }

    // ---------------------------------------------------------------------
    // Service: domain normalization
    // ---------------------------------------------------------------------

    public static function domainProvider(): array
    {
        return [
            'bare handle' => ['mystore', 'mystore.myshopify.com'],
            'full domain' => ['mystore.myshopify.com', 'mystore.myshopify.com'],
            'https url' => ['https://mystore.myshopify.com', 'mystore.myshopify.com'],
            'url with path' => ['https://mystore.myshopify.com/admin', 'mystore.myshopify.com'],
            'uppercase' => ['MyStore.MyShopify.com', 'mystore.myshopify.com'],
            'with spaces' => ['  mystore  ', 'mystore.myshopify.com'],
            'custom domain rejected' => ['mystore.com', null],
            'evil domain rejected' => ['evil.com/.myshopify.com', null],
            'empty rejected' => ['', null],
        ];
    }

    #[DataProvider('domainProvider')]
    public function test_normalize_shop_domain(string $input, ?string $expected): void
    {
        $service = new ShopifyOAuthService;

        $this->assertSame($expected, $service->normalizeShopDomain($input));
    }

    // ---------------------------------------------------------------------
    // Service: HMAC verification
    // ---------------------------------------------------------------------

    public function test_hmac_verification_accepts_valid_signature(): void
    {
        $service = new ShopifyOAuthService;
        $params = ['shop' => 'mystore.myshopify.com', 'code' => 'abc', 'state' => 'xyz'];
        $params['hmac'] = $this->computeHmac($params);

        $this->assertTrue($service->verifyHmac($params));
    }

    public function test_hmac_verification_rejects_tampered_signature(): void
    {
        $service = new ShopifyOAuthService;
        $params = ['shop' => 'mystore.myshopify.com', 'code' => 'abc', 'state' => 'xyz'];
        $params['hmac'] = $this->computeHmac($params);
        $params['code'] = 'tampered';

        $this->assertFalse($service->verifyHmac($params));
    }

    public function test_hmac_verification_rejects_missing_signature(): void
    {
        $service = new ShopifyOAuthService;

        $this->assertFalse($service->verifyHmac(['shop' => 'mystore.myshopify.com']));
    }

    // ---------------------------------------------------------------------
    // show: signed route
    // ---------------------------------------------------------------------

    public function test_show_rejects_unsigned_url(): void
    {
        $this->get(route('onboarding.shopify.show', $this->tenant))
            ->assertForbidden();
    }

    public function test_show_renders_connect_state_for_signed_url(): void
    {
        $this->get(ShopifyOnboardingController::signedShowUrl($this->tenant))
            ->assertOk()
            ->assertSee('Connect your Shopify store');
    }

    public function test_show_renders_connected_state_when_already_linked(): void
    {
        $this->tenant->update([
            'shopify_shop_domain' => 'mystore.myshopify.com',
            'shopify_admin_token' => 'shpat_existing',
        ]);

        $this->get(ShopifyOnboardingController::signedShowUrl($this->tenant))
            ->assertOk()
            ->assertSee('Your store is connected')
            ->assertSee('mystore.myshopify.com');
    }

    // ---------------------------------------------------------------------
    // connect
    // ---------------------------------------------------------------------

    public function test_connect_rejects_invalid_domain(): void
    {
        $this->from(ShopifyOnboardingController::signedShowUrl($this->tenant))
            ->post(route('onboarding.shopify.connect', $this->tenant), ['shop_domain' => 'not a domain!'])
            ->assertSessionHasErrors('shop_domain');
    }

    public function test_connect_redirects_to_shopify_and_stores_state(): void
    {
        $response = $this->post(
            route('onboarding.shopify.connect', $this->tenant),
            ['shop_domain' => 'mystore']
        );

        $response->assertRedirectContains('https://mystore.myshopify.com/admin/oauth/authorize');

        $oauth = session('shopify_oauth');
        $this->assertSame($this->tenant->id, $oauth['tenant_id']);
        $this->assertSame('mystore.myshopify.com', $oauth['shop']);
        $this->assertNotEmpty($oauth['state']);
        $this->assertStringContainsString('state='.$oauth['state'], $response->headers->get('Location'));
    }

    // ---------------------------------------------------------------------
    // callback
    // ---------------------------------------------------------------------

    public function test_callback_happy_path_stores_tokens(): void
    {
        Http::fake([
            '*/admin/oauth/access_token' => Http::response([
                'access_token' => 'shpat_new',
                'scope' => 'read_products,write_orders',
                'expires_in' => 3600,
                'refresh_token' => 'shprt_new',
            ]),
            '*/admin/api/*/graphql.json' => Http::response([
                'data' => ['storefrontAccessTokenCreate' => [
                    'storefrontAccessToken' => ['accessToken' => 'sft_new'],
                    'userErrors' => [],
                ]],
            ]),
        ]);

        $response = $this->withSession(['shopify_oauth' => [
            'state' => 'state123',
            'tenant_id' => $this->tenant->id,
            'shop' => 'mystore.myshopify.com',
        ]])->get($this->callbackUrl('mystore.myshopify.com', 'auth-code', 'state123'));

        $response->assertRedirect();

        $this->tenant->refresh();
        $this->assertSame('mystore.myshopify.com', $this->tenant->shopify_shop_domain);
        $this->assertSame('shpat_new', $this->tenant->shopify_admin_token);
        $this->assertSame('sft_new', $this->tenant->shopify_storefront_token);
        $this->assertSame('shprt_new', $this->tenant->shopify_admin_refresh_token);
        $this->assertNotNull($this->tenant->shopify_admin_token_expires_at);
        $this->assertTrue($this->tenant->shopify_admin_token_expires_at->greaterThan(now()->addMinutes(50)));
    }

    public function test_admin_token_service_refreshes_expired_token(): void
    {
        Http::fake([
            '*/admin/oauth/access_token' => Http::response([
                'access_token' => 'shpat_refreshed',
                'scope' => 'read_products',
                'expires_in' => 3600,
                'refresh_token' => 'shprt_rotated',
            ]),
        ]);

        $this->tenant->update([
            'shopify_shop_domain' => 'mystore.myshopify.com',
            'shopify_admin_token' => 'shpat_old',
            'shopify_admin_token_expires_at' => now()->subMinute(),
            'shopify_admin_refresh_token' => 'shprt_old',
        ]);

        $token = app(ShopifyAdminTokenService::class)->getValidToken($this->tenant->fresh());

        $this->assertSame('shpat_refreshed', $token);
        $this->assertSame('shpat_refreshed', $this->tenant->fresh()->shopify_admin_token);
        $this->assertSame('shprt_rotated', $this->tenant->fresh()->shopify_admin_refresh_token);
    }

    public function test_admin_token_service_returns_valid_token_without_refresh(): void
    {
        Http::fake();

        $this->tenant->update([
            'shopify_shop_domain' => 'mystore.myshopify.com',
            'shopify_admin_token' => 'shpat_current',
            'shopify_admin_token_expires_at' => now()->addMinutes(30),
            'shopify_admin_refresh_token' => 'shprt_current',
        ]);

        $token = app(ShopifyAdminTokenService::class)->getValidToken($this->tenant->fresh());

        $this->assertSame('shpat_current', $token);
        Http::assertNothingSent();
    }

    public function test_callback_rejects_mismatched_state(): void
    {
        Http::fake();

        $this->withSession(['shopify_oauth' => [
            'state' => 'expected-state',
            'tenant_id' => $this->tenant->id,
            'shop' => 'mystore.myshopify.com',
        ]])->get($this->callbackUrl('mystore.myshopify.com', 'auth-code', 'WRONG-state'))
            ->assertSessionHasErrors('shop_domain');

        $this->tenant->refresh();
        $this->assertNull($this->tenant->shopify_admin_token);
        Http::assertNothingSent();
    }

    public function test_callback_rejects_invalid_hmac(): void
    {
        Http::fake();

        // Valid state + shop, but a deliberately wrong hmac.
        $url = route('onboarding.shopify.callback', [
            'shop' => 'mystore.myshopify.com',
            'code' => 'auth-code',
            'state' => 'state123',
            'hmac' => 'deadbeef',
        ]);

        $this->withSession(['shopify_oauth' => [
            'state' => 'state123',
            'tenant_id' => $this->tenant->id,
            'shop' => 'mystore.myshopify.com',
        ]])->get($url)->assertSessionHasErrors('shop_domain');

        $this->tenant->refresh();
        $this->assertNull($this->tenant->shopify_admin_token);
        Http::assertNothingSent();
    }

    // ---------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------

    private function computeHmac(array $params): string
    {
        unset($params['hmac'], $params['signature']);
        ksort($params);

        return hash_hmac(
            'sha256',
            urldecode(http_build_query($params)),
            config('services.shopify.client_secret')
        );
    }

    private function callbackUrl(string $shop, string $code, string $state): string
    {
        $params = ['shop' => $shop, 'code' => $code, 'state' => $state, 'timestamp' => '1700000000'];
        $params['hmac'] = $this->computeHmac($params);

        return route('onboarding.shopify.callback', $params);
    }
}

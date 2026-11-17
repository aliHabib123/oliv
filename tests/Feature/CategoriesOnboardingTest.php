<?php

namespace Tests\Feature;

use App\Http\Controllers\Onboarding\CategoriesController;
use App\Models\AppConfiguration;
use App\Models\Tenant;
use App\Services\Shopify\ShopifyAdminService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class CategoriesOnboardingTest extends TestCase
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

    private function collections(): array
    {
        return [
            ['id' => 'gid://shopify/Collection/1', 'title' => 'Shoes', 'handle' => 'shoes', 'image_url' => null],
            ['id' => 'gid://shopify/Collection/2', 'title' => 'Bags', 'handle' => 'bags', 'image_url' => null],
        ];
    }

    public function test_show_requires_signed_url(): void
    {
        $this->get(route('onboarding.categories.show', $this->tenant))->assertForbidden();
    }

    public function test_show_redirects_to_shopify_when_not_connected(): void
    {
        $this->tenant->update(['shopify_admin_token' => null]);

        $this->get(CategoriesController::signedShowUrl($this->tenant))
            ->assertRedirectContains('/onboarding/'.$this->tenant->id.'/shopify');
    }

    public function test_show_lists_collections(): void
    {
        $this->mock(ShopifyAdminService::class, function (MockInterface $mock) {
            $mock->shouldReceive('getCollections')->once()->andReturn($this->collections());
        });

        $this->get(CategoriesController::signedShowUrl($this->tenant))
            ->assertOk()
            ->assertSee('Shoes')
            ->assertSee('Bags');
    }

    public function test_save_requires_a_selection(): void
    {
        $this->from(CategoriesController::signedShowUrl($this->tenant))
            ->post(route('onboarding.categories.save', $this->tenant), [])
            ->assertSessionHasErrors('selected_ids');
    }

    public function test_save_builds_flutter_menu_and_persists_selection(): void
    {
        $this->mock(ShopifyAdminService::class, function (MockInterface $mock) {
            $mock->shouldReceive('getCollections')->andReturn($this->collections());
            $mock->shouldReceive('upsertFlutterMenu')
                ->once()
                ->withArgs(function (Tenant $t, array $chosen) {
                    // Order preserved from the submitted selection (Bags first, then Shoes).
                    return $chosen[0]['title'] === 'Bags' && $chosen[1]['title'] === 'Shoes';
                })
                ->andReturn(['menu_id' => 'gid://shopify/Menu/9']);
        });

        $this->post(route('onboarding.categories.save', $this->tenant), [
            'selected_ids' => ['gid://shopify/Collection/2', 'gid://shopify/Collection/1'],
        ])->assertRedirectContains('/onboarding/'.$this->tenant->id.'/store-info');

        $config = AppConfiguration::where('tenant_id', $this->tenant->id)->first();
        $this->assertSame(
            ['gid://shopify/Collection/2', 'gid://shopify/Collection/1'],
            $config->config['categories']['collection_ids']
        );
        $this->assertTrue($config->config['basic']['sync_level_0_categories']);
    }

    public function test_save_shows_error_when_shopify_fails(): void
    {
        $this->mock(ShopifyAdminService::class, function (MockInterface $mock) {
            $mock->shouldReceive('getCollections')->andReturn($this->collections());
            $mock->shouldReceive('upsertFlutterMenu')->andThrow(new \RuntimeException('boom'));
        });

        $this->from(CategoriesController::signedShowUrl($this->tenant))
            ->post(route('onboarding.categories.save', $this->tenant), [
                'selected_ids' => ['gid://shopify/Collection/1'],
            ])
            ->assertSessionHasErrors('selected_ids');
    }
}

# Design Document: Shopify Onboarding Wizard

## Overview

An 8-step onboarding wizard that guides new merchants from account creation to a fully configured mobile app in under 5 minutes. The wizard connects to Shopify via OAuth, auto-imports store data, provides AI-powered recommendations, and queues an app build — minimizing manual input by leveraging Shopify as the primary data source.

Key design decisions:
- Standalone Livewire multi-step wizard at `/onboarding` (outside Filament admin)
- StoreAdapter pattern for platform extensibility (Shopify now, others later)
- Async import with queue jobs + polling for non-blocking UX
- AES-256-GCM encryption for access tokens
- AI recommendation engine using industry mapping + signal boosters

## Architecture

The onboarding wizard is a standalone Livewire multi-step form served at `/onboarding`, fully independent of the Filament admin panel. It orchestrates OAuth connection, async data import, AI-powered recommendations, and app configuration generation through a layered architecture.

### Component Diagram

```
┌─────────────────────────────────────────────────────────────────────────┐
│                         Browser (Merchant)                               │
│  ┌───────────────────────────────────────────────────────────────────┐  │
│  │              Livewire Wizard Component (OnboardingWizard)          │  │
│  │  ┌──────┐ ┌──────┐ ┌──────┐ ┌──────┐ ┌──────┐ ┌──────┐ ┌──────┐│  │
│  │  │Step 1│ │Step 2│ │Step 3│ │Step 4│ │Step 5│ │Step 6│ │Step 7││  │
│  │  │Select│ │OAuth │ │Review│ │Brand │ │Feats │ │Theme │ │Final ││  │
│  │  └──────┘ └──────┘ └──────┘ └──────┘ └──────┘ └──────┘ └──────┘│  │
│  └───────────────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────────────┘
          │ Livewire HTTP                          │ OAuth redirect
          ▼                                        ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                         Laravel Application                              │
│                                                                         │
│  ┌──────────────────┐    ┌──────────────────────────────────────────┐  │
│  │   Routes (web)    │    │         OnboardingWizard (Livewire)       │  │
│  │  /onboarding      │───▶│  - manages step transitions              │  │
│  │  /onboarding/     │    │  - validates input per step              │  │
│  │    shopify/callback│    │  - persists to OnboardingSession         │  │
│  └──────────────────┘    └──────────┬───────────────────────────────┘  │
│                                      │                                   │
│         ┌────────────────────────────┼────────────────────────┐         │
│         ▼                            ▼                        ▼         │
│  ┌─────────────┐  ┌──────────────────────┐  ┌──────────────────────┐  │
│  │OAuthService │  │   StoreAdapter        │  │AIRecommendationEngine│  │
│  │- redirect   │  │   (interface)         │  │- industry mapping    │  │
│  │- callback   │  │   ┌──────────────┐   │  │- signal boosters     │  │
│  │- validate   │  │   │ShopifyAdapter│   │  │- scoring             │  │
│  │- exchange   │  │   └──────────────┘   │  └──────────────────────┘  │
│  └─────────────┘  │   ┌──────────────┐   │                            │
│                    │   │ManualAdapter │   │                            │
│                    │   └──────────────┘   │                            │
│                    └──────────────────────┘                            │
│                              │                                          │
│                              ▼                                          │
│  ┌──────────────────────────────────────────────────────────────────┐  │
│  │                    Existing Services                               │  │
│  │  ShopifyAdminService │ ShopifyAdminTokenService │ ShopifyStorefront│  │
│  └──────────────────────────────────────────────────────────────────┘  │
│                              │                                          │
│         ┌────────────────────┼──────────────────────────┐              │
│         ▼                    ▼                           ▼              │
│  ┌─────────────┐  ┌──────────────────┐  ┌──────────────────────────┐  │
│  │ Queue Jobs   │  │    Models         │  │     Database (MySQL)      │  │
│  │- ImportJob   │  │- OnboardingSession│  │- onboarding_sessions     │  │
│  │- ProvisionJob│  │- AppConfig       │  │- app_configs             │  │
│  │- CatalogSync │  │- FeatureFlag     │  │- feature_flags           │  │
│  └─────────────┘  │- AppTheme        │  │- app_themes              │  │
│                    └──────────────────┘  └──────────────────────────┘  │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## Components and Interfaces

### Key Components

| Component | Type | Responsibility |
|-----------|------|----------------|
| `OnboardingWizard` | Livewire Component | Step management, validation, state persistence |
| `OnboardingController` | Controller | OAuth callback handling, import status polling |
| `ShopifyOAuthService` | Service | OAuth flow, HMAC/state/domain validation, token exchange |
| `ShopifyAdapter` | StoreAdapter | Shopify API data fetching (wraps ShopifyAdminService) |
| `ManualAdapter` | StoreAdapter | Empty defaults for manual entry flow |
| `AIRecommendationEngine` | Service | Industry mapping, signal boosters, feature/theme scoring |
| `AppConfigGenerator` | Service | Generates AppConfig + FeatureFlags from wizard payload |
| `ImportShopifyDataJob` | Queue Job | Async parallel import of store data |

## Data Models

### Database Schema (Migrations)

### Migration: `create_onboarding_sessions_table`

```php
Schema::create('onboarding_sessions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
    $table->string('platform')->default('shopify'); // shopify | manual
    $table->unsignedTinyInteger('step')->default(1);
    $table->string('shopify_domain')->nullable();
    $table->json('imported_data')->nullable();
    $table->json('wizard_payload')->nullable();
    $table->json('ai_recommended')->nullable();
    $table->timestamp('completed_at')->nullable();
    $table->timestamps();

    $table->index('tenant_id');
});
```

### Migration: `create_app_configs_table`

```php
Schema::create('app_configs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
    $table->string('app_name');
    $table->string('app_icon_url')->nullable();
    $table->string('splash_url')->nullable();
    $table->string('primary_color', 7)->default('#000000');
    $table->string('secondary_color', 7)->default('#ffffff');
    $table->string('font_family')->default('Inter');
    $table->foreignId('theme_id')->nullable()->constrained('app_themes')->nullOnDelete();
    $table->json('home_layout')->nullable();
    $table->json('navigation')->nullable();
    $table->string('publish_status')->default('draft'); // draft | building | published
    $table->string('bundle_id_ios')->nullable();
    $table->string('bundle_id_android')->nullable();
    $table->timestamps();

    $table->unique('tenant_id');
});
```

### Migration: `create_feature_flags_table`

```php
Schema::create('feature_flags', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
    $table->string('feature_key');
    $table->boolean('enabled')->default(true);
    $table->json('config')->nullable();
    $table->timestamps();

    $table->unique(['tenant_id', 'feature_key']);
});
```

### Migration: `create_app_themes_table`

```php
Schema::create('app_themes', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->json('default_config')->nullable();
    $table->timestamps();
});
```

### Seeder: `AppThemeSeeder`

```php
AppTheme::insert([
    ['name' => 'Minimal', 'default_config' => json_encode([
        'nav_style' => 'bottom_bar', 'card_style' => 'flat', 'grid_columns' => 2
    ])],
    ['name' => 'Bold', 'default_config' => json_encode([
        'nav_style' => 'bottom_bar', 'card_style' => 'elevated', 'grid_columns' => 1
    ])],
    ['name' => 'Classic', 'default_config' => json_encode([
        'nav_style' => 'drawer', 'card_style' => 'bordered', 'grid_columns' => 2
    ])],
    ['name' => 'Modern', 'default_config' => json_encode([
        'nav_style' => 'bottom_bar', 'card_style' => 'rounded', 'grid_columns' => 2
    ])],
    ['name' => 'Luxe', 'default_config' => json_encode([
        'nav_style' => 'bottom_bar', 'card_style' => 'elevated', 'grid_columns' => 1
    ])],
    ['name' => 'Fresh', 'default_config' => json_encode([
        'nav_style' => 'tab_bar', 'card_style' => 'flat', 'grid_columns' => 3
    ])],
]);
```

---

## StoreAdapter Interface and Implementations

### Interface: `App\Contracts\StoreAdapter`

```php
<?php

namespace App\Contracts;

use App\Models\Tenant;

interface StoreAdapter
{
    /**
     * Fetch store information (name, industry, description, contact).
     *
     * @return array{name: string, industry: ?string, description: ?string, email: ?string, phone: ?string, address: ?array}
     */
    public function fetchStoreInfo(Tenant $tenant): array;

    /**
     * Fetch product catalog summary (categories, product count, sample products).
     *
     * @return array{product_count: int, categories: array, sample_products: array}
     */
    public function fetchCatalog(Tenant $tenant): array;

    /**
     * Fetch branding assets (logo, colors, fonts from theme).
     *
     * @return array{logo_url: ?string, primary_color: ?string, secondary_color: ?string, font_family: ?string}
     */
    public function fetchBranding(Tenant $tenant): array;
}
```

### Implementation: `App\Services\Onboarding\ShopifyAdapter`

```php
<?php

namespace App\Services\Onboarding;

use App\Contracts\StoreAdapter;
use App\Models\Tenant;
use App\Services\Shopify\ShopifyAdminService;
use App\Services\Shopify\ShopifyAdminTokenService;
use Illuminate\Support\Facades\Http;

class ShopifyAdapter implements StoreAdapter
{
    public function __construct(
        private ShopifyAdminService $adminService,
        private ShopifyAdminTokenService $tokenService,
    ) {}

    public function fetchStoreInfo(Tenant $tenant): array
    {
        $token = $this->tokenService->getValidToken($tenant);
        $domain = $tenant->shopify_shop_domain;

        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $token,
        ])->get("https://{$domain}/admin/api/2025-10/shop.json");

        $shop = $response->json('shop');

        return [
            'name' => $shop['name'] ?? '',
            'industry' => $shop['industry'] ?? null,
            'description' => $shop['description'] ?? null,
            'email' => $shop['email'] ?? null,
            'phone' => $shop['phone'] ?? null,
            'address' => [
                'address1' => $shop['address1'] ?? null,
                'city' => $shop['city'] ?? null,
                'country' => $shop['country_name'] ?? null,
            ],
        ];
    }

    public function fetchCatalog(Tenant $tenant): array
    {
        $token = $this->tokenService->getValidToken($tenant);
        $domain = $tenant->shopify_shop_domain;

        $query = <<<'GRAPHQL'
            query {
                productsCount { count }
                collections(first: 20) {
                    edges { node { id title handle } }
                }
                products(first: 10) {
                    edges { node { id title productType featuredImage { url } } }
                }
            }
        GRAPHQL;

        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $token,
            'Content-Type' => 'application/json',
        ])->post("https://{$domain}/admin/api/2025-10/graphql.json", [
            'query' => $query,
        ]);

        $data = $response->json('data');

        return [
            'product_count' => $data['productsCount']['count'] ?? 0,
            'categories' => collect($data['collections']['edges'] ?? [])
                ->pluck('node.title')->all(),
            'sample_products' => collect($data['products']['edges'] ?? [])
                ->map(fn($edge) => [
                    'title' => $edge['node']['title'],
                    'type' => $edge['node']['productType'],
                    'image' => $edge['node']['featuredImage']['url'] ?? null,
                ])->all(),
        ];
    }

    public function fetchBranding(Tenant $tenant): array
    {
        $token = $this->tokenService->getValidToken($tenant);
        $domain = $tenant->shopify_shop_domain;

        $query = <<<'GRAPHQL'
            query {
                shop { brand { logo { image { url } } colors { primary { background } secondary { background } } } }
            }
        GRAPHQL;

        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $token,
            'Content-Type' => 'application/json',
        ])->post("https://{$domain}/admin/api/2025-10/graphql.json", [
            'query' => $query,
        ]);

        $brand = $response->json('data.shop.brand');

        return [
            'logo_url' => $brand['logo']['image']['url'] ?? null,
            'primary_color' => $brand['colors']['primary'][0]['background'] ?? null,
            'secondary_color' => $brand['colors']['secondary'][0]['background'] ?? null,
            'font_family' => null, // Shopify doesn't expose font via API
        ];
    }
}
```

### Implementation: `App\Services\Onboarding\ManualAdapter`

```php
<?php

namespace App\Services\Onboarding;

use App\Contracts\StoreAdapter;
use App\Models\Tenant;

class ManualAdapter implements StoreAdapter
{
    public function fetchStoreInfo(Tenant $tenant): array
    {
        return [
            'name' => $tenant->name ?? '',
            'industry' => null,
            'description' => null,
            'email' => null,
            'phone' => null,
            'address' => null,
        ];
    }

    public function fetchCatalog(Tenant $tenant): array
    {
        return [
            'product_count' => 0,
            'categories' => [],
            'sample_products' => [],
        ];
    }

    public function fetchBranding(Tenant $tenant): array
    {
        return [
            'logo_url' => null,
            'primary_color' => null,
            'secondary_color' => null,
            'font_family' => null,
        ];
    }
}
```

---

## OAuth Flow with Security

### OAuth Service: `App\Services\Onboarding\ShopifyOAuthService`

```php
<?php

namespace App\Services\Onboarding;

use App\Models\OnboardingSession;
use App\Models\Tenant;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class ShopifyOAuthService
{
    private const REQUIRED_SCOPES = [
        'read_products',
        'read_themes',
        'read_content',
        'read_orders',
    ];

    /**
     * Generate the OAuth authorization URL.
     */
    public function buildAuthorizationUrl(OnboardingSession $session): string
    {
        $state = Str::random(40);
        $session->update(['wizard_payload->oauth_state' => $state]);

        $params = http_build_query([
            'client_id' => config('services.shopify.client_id'),
            'scope' => implode(',', self::REQUIRED_SCOPES),
            'redirect_uri' => route('onboarding.shopify.callback'),
            'state' => $state,
        ]);

        $domain = $session->shopify_domain;

        return "https://{$domain}/admin/oauth/authorize?{$params}";
    }

    /**
     * Validate the OAuth callback parameters.
     *
     * @throws \App\Exceptions\OAuthValidationException
     */
    public function validateCallback(array $params, OnboardingSession $session): void
    {
        $this->validateHmac($params);
        $this->validateState($params['state'] ?? '', $session);
        $this->validateShopDomain($params['shop'] ?? '');
    }

    /**
     * Validate HMAC signature from Shopify callback.
     */
    public function validateHmac(array $params): bool
    {
        $hmac = $params['hmac'] ?? '';
        $filteredParams = collect($params)
            ->except(['hmac', 'signature'])
            ->sortKeys()
            ->map(fn($value, $key) => "{$key}={$value}")
            ->implode('&');

        $computed = hash_hmac(
            'sha256',
            $filteredParams,
            config('services.shopify.client_secret')
        );

        if (! hash_equals($computed, $hmac)) {
            throw new \App\Exceptions\OAuthValidationException('Invalid HMAC signature');
        }

        return true;
    }

    /**
     * Validate state parameter matches session.
     */
    public function validateState(string $state, OnboardingSession $session): bool
    {
        $expected = $session->wizard_payload['oauth_state'] ?? '';

        if (! hash_equals($expected, $state)) {
            throw new \App\Exceptions\OAuthValidationException('Invalid state parameter');
        }

        return true;
    }

    /**
     * Validate shop domain matches *.myshopify.com pattern.
     */
    public function validateShopDomain(string $shop): bool
    {
        $pattern = '/^[a-zA-Z0-9][a-zA-Z0-9\-]*\.myshopify\.com$/';

        if (! preg_match($pattern, $shop)) {
            throw new \App\Exceptions\OAuthValidationException('Invalid shop domain');
        }

        return true;
    }

    /**
     * Validate that returned scopes include all required scopes.
     */
    public function validateScopes(string $grantedScopes): bool
    {
        $granted = explode(',', $grantedScopes);
        $missing = array_diff(self::REQUIRED_SCOPES, $granted);

        if (! empty($missing)) {
            throw new \App\Exceptions\OAuthValidationException(
                'Missing required scopes: ' . implode(', ', $missing)
            );
        }

        return true;
    }

    /**
     * Exchange authorization code for access token and store encrypted on tenant.
     */
    public function exchangeCodeForToken(string $code, string $shop, Tenant $tenant): string
    {
        $response = Http::post("https://{$shop}/admin/oauth/access_token", [
            'client_id' => config('services.shopify.client_id'),
            'client_secret' => config('services.shopify.client_secret'),
            'code' => $code,
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Token exchange failed: ' . $response->body());
        }

        $data = $response->json();
        $accessToken = $data['access_token'];

        // Encrypt with AES-256-GCM via Laravel's encrypter
        $tenant->update([
            'shopify_shop_domain' => $shop,
            'shopify_admin_token' => Crypt::encryptString($accessToken),
            'shopify_admin_token_expires_at' => now()->addHours(24),
        ]);

        return $accessToken;
    }
}
```

### Token Encryption

Laravel's `Crypt::encryptString()` uses AES-256-CBC by default. To use AES-256-GCM, the application's `config/app.php` cipher is set to `aes-256-gcm`:

```php
// config/app.php
'cipher' => 'aes-256-gcm',
```

The `ShopifyAdminTokenService` is updated to decrypt the token before API calls:

```php
// In ShopifyAdminTokenService::getValidToken()
use Illuminate\Support\Facades\Crypt;

$token = Crypt::decryptString($tenant->shopify_admin_token);
```

### Token Exclusion

The Tenant model hides the raw token from serialization:

```php
// App\Models\Tenant
protected $hidden = ['shopify_admin_token', 'shopify_client_secret'];
```

---

## Import Job Design

### Job: `App\Jobs\ImportShopifyDataJob`

```php
<?php

namespace App\Jobs;

use App\Contracts\StoreAdapter;
use App\Models\OnboardingSession;
use App\Services\Onboarding\AIRecommendationEngine;
use App\Services\Onboarding\ShopifyAdapter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ImportShopifyDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [10, 30, 60];

    public function __construct(
        private int $onboardingSessionId,
    ) {}

    public function handle(ShopifyAdapter $adapter, AIRecommendationEngine $engine): void
    {
        $session = OnboardingSession::findOrFail($this->onboardingSessionId);
        $tenant = $session->tenant;

        // Parallel fetches using Laravel's concurrent HTTP
        $results = \Illuminate\Support\Facades\Http::pool(fn($pool) => [
            $pool->as('store_info')->withToken('internal')
                ->get(route('internal.fetch-store-info', $session->id)),
            $pool->as('catalog')->withToken('internal')
                ->get(route('internal.fetch-catalog', $session->id)),
            $pool->as('branding')->withToken('internal')
                ->get(route('internal.fetch-branding', $session->id)),
        ]);

        // Alternative: direct parallel execution via concurrency
        $storeInfo = $adapter->fetchStoreInfo($tenant);
        $catalog = $adapter->fetchCatalog($tenant);
        $branding = $adapter->fetchBranding($tenant);

        $importedData = [
            'store_info' => $storeInfo,
            'catalog' => $catalog,
            'branding' => $branding,
            'imported_at' => now()->toISOString(),
        ];

        $session->update(['imported_data' => $importedData]);

        // Trigger AI recommendations in background
        $engine->generateRecommendations($session);

        Log::info('ImportShopifyDataJob: completed', [
            'session_id' => $session->id,
            'tenant_id' => $tenant->id,
            'product_count' => $catalog['product_count'],
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        $session = OnboardingSession::find($this->onboardingSessionId);
        if ($session) {
            $session->update([
                'imported_data' => ['error' => $exception->getMessage(), 'failed_at' => now()->toISOString()],
            ]);
        }

        Log::error('ImportShopifyDataJob: permanently failed', [
            'session_id' => $this->onboardingSessionId,
            'error' => $exception->getMessage(),
        ]);
    }
}
```

### Retry Logic

- 3 attempts with exponential backoff: 10s, 30s, 60s
- On permanent failure: marks session with error, allows manual fallback
- Each Shopify API call has a 30s HTTP timeout (matching existing `ShopifyAdminService` pattern)

---

## AI Recommendation Engine

### Service: `App\Services\Onboarding\AIRecommendationEngine`

```php
<?php

namespace App\Services\Onboarding;

use App\Models\OnboardingSession;

class AIRecommendationEngine
{
    /**
     * Industry-to-feature mapping table.
     * Maps Shopify industry values to recommended feature keys.
     */
    private const INDUSTRY_FEATURES = [
        'fashion' => ['wishlist', 'size_guide', 'lookbook', 'loyalty_program', 'push_notifications'],
        'food_and_drink' => ['reorder', 'delivery_tracking', 'push_notifications', 'loyalty_program'],
        'electronics' => ['compare', 'reviews', 'wishlist', 'push_notifications'],
        'health_and_beauty' => ['subscriptions', 'reviews', 'loyalty_program', 'push_notifications'],
        'home_and_garden' => ['wishlist', 'ar_preview', 'reviews', 'push_notifications'],
        'sports' => ['wishlist', 'size_guide', 'loyalty_program', 'push_notifications'],
        'jewelry' => ['wishlist', 'lookbook', 'loyalty_program', 'push_notifications', 'ar_preview'],
        'default' => ['wishlist', 'push_notifications', 'reviews'],
    ];

    /**
     * Industry-to-theme mapping for initial theme scoring.
     */
    private const INDUSTRY_THEMES = [
        'fashion' => 'Bold',
        'food_and_drink' => 'Fresh',
        'electronics' => 'Modern',
        'health_and_beauty' => 'Minimal',
        'home_and_garden' => 'Classic',
        'sports' => 'Bold',
        'jewelry' => 'Luxe',
        'default' => 'Modern',
    ];

    /**
     * Signal boosters adjust scores based on store characteristics.
     */
    private const SIGNAL_BOOSTERS = [
        'high_product_count' => ['compare', 'search_filters'],    // > 50 products
        'has_categories' => ['category_navigation'],               // > 3 categories
        'has_physical_products' => ['delivery_tracking', 'size_guide'],
        'has_logo' => [],                                          // boosts Luxe/Bold themes
    ];

    /**
     * Generate feature and theme recommendations from imported data.
     */
    public function generateRecommendations(OnboardingSession $session): void
    {
        $importedData = $session->imported_data;
        if (empty($importedData) || isset($importedData['error'])) {
            return;
        }

        $industry = $this->resolveIndustry($importedData);
        $features = $this->scoreFeatures($industry, $importedData);
        $theme = $this->scoreTheme($industry, $importedData);

        $session->update([
            'ai_recommended' => [
                'features' => $features,
                'theme_id' => $theme,
                'industry' => $industry,
                'generated_at' => now()->toISOString(),
            ],
        ]);
    }

    /**
     * Resolve the store industry from imported data signals.
     */
    public function resolveIndustry(array $importedData): string
    {
        $storeInfo = $importedData['store_info'] ?? [];

        // Primary: Shopify's industry field
        if (! empty($storeInfo['industry'])) {
            $normalized = str_replace([' ', '&', '-'], '_', strtolower($storeInfo['industry']));
            if (array_key_exists($normalized, self::INDUSTRY_FEATURES)) {
                return $normalized;
            }
        }

        // Secondary: infer from product types in catalog
        $catalog = $importedData['catalog'] ?? [];
        $productTypes = collect($catalog['sample_products'] ?? [])
            ->pluck('type')
            ->filter()
            ->map(fn($t) => strtolower($t));

        $industryKeywords = [
            'fashion' => ['clothing', 'apparel', 'dress', 'shirt', 'shoes'],
            'food_and_drink' => ['food', 'beverage', 'coffee', 'tea', 'snack'],
            'electronics' => ['electronics', 'gadget', 'phone', 'laptop', 'computer'],
            'health_and_beauty' => ['beauty', 'skincare', 'cosmetics', 'health', 'wellness'],
            'jewelry' => ['jewelry', 'ring', 'necklace', 'bracelet', 'watch'],
        ];

        foreach ($industryKeywords as $industry => $keywords) {
            if ($productTypes->contains(fn($type) => collect($keywords)->contains(fn($kw) => str_contains($type, $kw)))) {
                return $industry;
            }
        }

        return 'default';
    }

    /**
     * Score and select features based on industry + signal boosters.
     *
     * @return array<string, bool> Feature key => enabled
     */
    public function scoreFeatures(string $industry, array $importedData): array
    {
        $baseFeatures = self::INDUSTRY_FEATURES[$industry] ?? self::INDUSTRY_FEATURES['default'];
        $boostedFeatures = [];

        $catalog = $importedData['catalog'] ?? [];
        $productCount = $catalog['product_count'] ?? 0;
        $categoryCount = count($catalog['categories'] ?? []);

        if ($productCount > 50) {
            $boostedFeatures = array_merge($boostedFeatures, self::SIGNAL_BOOSTERS['high_product_count']);
        }
        if ($categoryCount > 3) {
            $boostedFeatures = array_merge($boostedFeatures, self::SIGNAL_BOOSTERS['has_categories']);
        }

        $allRecommended = array_unique(array_merge($baseFeatures, $boostedFeatures));

        // Return as key => bool map for all available features
        $allFeatures = [
            'wishlist', 'push_notifications', 'reviews', 'loyalty_program',
            'reorder', 'delivery_tracking', 'size_guide', 'lookbook',
            'compare', 'search_filters', 'category_navigation', 'subscriptions',
            'ar_preview',
        ];

        return collect($allFeatures)
            ->mapWithKeys(fn($feature) => [$feature => in_array($feature, $allRecommended)])
            ->all();
    }

    /**
     * Score and select a theme based on industry + branding signals.
     */
    public function scoreTheme(string $industry, array $importedData): int
    {
        $themeName = self::INDUSTRY_THEMES[$industry] ?? self::INDUSTRY_THEMES['default'];

        // Signal booster: if store has a logo and uses dark colors, prefer Luxe/Bold
        $branding = $importedData['branding'] ?? [];
        if (! empty($branding['logo_url']) && $this->isDarkColor($branding['primary_color'] ?? '')) {
            $themeName = 'Luxe';
        }

        return \App\Models\AppTheme::where('name', $themeName)->value('id') ?? 1;
    }

    private function isDarkColor(?string $hex): bool
    {
        if (! $hex || ! preg_match('/^#[0-9a-fA-F]{6}$/', $hex)) {
            return false;
        }

        $r = hexdec(substr($hex, 1, 2));
        $g = hexdec(substr($hex, 3, 2));
        $b = hexdec(substr($hex, 5, 2));
        $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;

        return $luminance < 0.5;
    }
}
```

---

## Livewire Wizard Components Structure

### Main Component: `App\Livewire\OnboardingWizard`

```php
<?php

namespace App\Livewire;

use App\Models\OnboardingSession;
use App\Models\Tenant;
use Livewire\Component;

class OnboardingWizard extends Component
{
    public int $currentStep = 1;
    public int $totalSteps = 8;
    public ?OnboardingSession $session = null;

    // Step data properties
    public string $platform = '';
    public string $shopifyDomain = '';
    public array $storeData = [];
    public array $branding = [];
    public array $features = [];
    public ?int $selectedThemeId = null;

    public function mount(): void
    {
        $tenant = auth()->user()->tenant;
        $this->session = OnboardingSession::firstOrCreate(
            ['tenant_id' => $tenant->id, 'completed_at' => null],
            ['platform' => '', 'step' => 1]
        );

        $this->currentStep = $this->session->step;
        $this->hydrateFromSession();
    }

    public function nextStep(): void
    {
        $this->validateCurrentStep();
        $this->persistCurrentStep();
        $this->currentStep = min($this->currentStep + 1, $this->totalSteps);
        $this->session->update(['step' => $this->currentStep]);
    }

    public function previousStep(): void
    {
        $this->currentStep = max($this->currentStep - 1, 1);
        $this->session->update(['step' => $this->currentStep]);
    }

    public function render()
    {
        return view('livewire.onboarding-wizard')
            ->layout('layouts.onboarding');
    }

    private function hydrateFromSession(): void
    {
        $this->platform = $this->session->platform ?? '';
        $this->shopifyDomain = $this->session->shopify_domain ?? '';
        $payload = $this->session->wizard_payload ?? [];
        $this->branding = $payload['branding'] ?? [];
        $this->features = $this->session->ai_recommended['features'] ?? [];
        $this->selectedThemeId = $payload['theme_id'] ?? null;
    }

    private function validateCurrentStep(): void
    {
        match ($this->currentStep) {
            1 => $this->validate(['platform' => 'required|in:shopify,manual']),
            3 => $this->validate(['storeData.name' => 'required|string|max:255']),
            4 => $this->validate([
                'branding.app_name' => 'required|string|max:255',
                'branding.primary_color' => 'required|regex:/^#[0-9a-fA-F]{6}$/',
                'branding.secondary_color' => 'required|regex:/^#[0-9a-fA-F]{6}$/',
            ]),
            default => null,
        };
    }

    private function persistCurrentStep(): void
    {
        $payload = $this->session->wizard_payload ?? [];

        match ($this->currentStep) {
            1 => $this->session->update(['platform' => $this->platform]),
            3 => $this->session->update(['wizard_payload' => array_merge($payload, ['store_data' => $this->storeData])]),
            4 => $this->session->update(['wizard_payload' => array_merge($payload, ['branding' => $this->branding])]),
            5 => $this->session->update(['wizard_payload' => array_merge($payload, ['features' => $this->features])]),
            6 => $this->session->update(['wizard_payload' => array_merge($payload, ['theme_id' => $this->selectedThemeId])]),
            default => null,
        };
    }
}
```

### Step Components (Blade partials)

```
resources/views/livewire/onboarding-wizard.blade.php    (main layout + step router)
resources/views/livewire/steps/platform-select.blade.php (Step 1)
resources/views/livewire/steps/shopify-connect.blade.php (Step 2)
resources/views/livewire/steps/data-review.blade.php     (Step 3)
resources/views/livewire/steps/branding.blade.php        (Step 4)
resources/views/livewire/steps/features.blade.php        (Step 5)
resources/views/livewire/steps/theme-select.blade.php    (Step 6)
resources/views/livewire/steps/review-generate.blade.php (Step 7)
resources/views/livewire/steps/completion.blade.php      (Step 8)
resources/views/layouts/onboarding.blade.php             (standalone layout)
```

Each step blade partial receives the wizard state through the parent component's public properties and dispatches events back via `wire:click`.

---

## Route Definitions

```php
// routes/web.php (additions)

use App\Http\Controllers\OnboardingController;
use App\Livewire\OnboardingWizard;

Route::middleware('auth')->group(function () {
    // Main wizard route (Livewire full-page component)
    Route::get('/onboarding', OnboardingWizard::class)
        ->name('onboarding.index');

    // OAuth callback (standard controller — not Livewire)
    Route::get('/onboarding/shopify/callback', [OnboardingController::class, 'shopifyCallback'])
        ->name('onboarding.shopify.callback');

    // OAuth initiation (redirects to Shopify)
    Route::post('/onboarding/shopify/connect', [OnboardingController::class, 'shopifyConnect'])
        ->name('onboarding.shopify.connect');

    // Polling endpoint for import status
    Route::get('/onboarding/import-status', [OnboardingController::class, 'importStatus'])
        ->name('onboarding.import-status');
});
```

---

## App Config Generation Logic

### Service: `App\Services\Onboarding\AppConfigGenerator`

```php
<?php

namespace App\Services\Onboarding;

use App\Models\AppConfig;
use App\Models\AppTheme;
use App\Models\FeatureFlag;
use App\Models\OnboardingSession;
use Illuminate\Support\Str;

class AppConfigGenerator
{
    /**
     * Generate the full app configuration from a completed onboarding session.
     */
    public function generate(OnboardingSession $session): AppConfig
    {
        $payload = $session->wizard_payload ?? [];
        $branding = $payload['branding'] ?? [];
        $themeId = $payload['theme_id'] ?? null;
        $features = $payload['features'] ?? $session->ai_recommended['features'] ?? [];
        $tenant = $session->tenant;

        // Create AppConfig
        $appConfig = AppConfig::updateOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'app_name' => $branding['app_name'] ?? $tenant->name,
                'app_icon_url' => $branding['app_icon_url'] ?? null,
                'splash_url' => $branding['splash_url'] ?? null,
                'primary_color' => $branding['primary_color'] ?? '#000000',
                'secondary_color' => $branding['secondary_color'] ?? '#ffffff',
                'font_family' => $branding['font_family'] ?? 'Inter',
                'theme_id' => $themeId,
                'home_layout' => $this->buildHomeLayout($themeId),
                'navigation' => $this->buildNavigation($features),
                'publish_status' => 'building',
                'bundle_id_ios' => $this->generateBundleId($tenant->name, 'ios'),
                'bundle_id_android' => $this->generateBundleId($tenant->name, 'android'),
            ]
        );

        // Create FeatureFlags
        foreach ($features as $featureKey => $enabled) {
            FeatureFlag::updateOrCreate(
                ['tenant_id' => $tenant->id, 'feature_key' => $featureKey],
                ['enabled' => (bool) $enabled]
            );
        }

        // Mark session complete
        $session->update(['completed_at' => now()]);

        return $appConfig;
    }

    private function buildHomeLayout(?int $themeId): array
    {
        if (! $themeId) {
            return ['sections' => ['hero', 'categories', 'featured_products', 'new_arrivals']];
        }

        $theme = AppTheme::find($themeId);
        $config = $theme?->default_config ?? [];

        return [
            'sections' => ['hero', 'categories', 'featured_products', 'new_arrivals'],
            'grid_columns' => $config['grid_columns'] ?? 2,
            'card_style' => $config['card_style'] ?? 'flat',
            'nav_style' => $config['nav_style'] ?? 'bottom_bar',
        ];
    }

    private function buildNavigation(array $features): array
    {
        $tabs = [
            ['key' => 'home', 'label' => 'Home', 'icon' => 'home'],
            ['key' => 'categories', 'label' => 'Shop', 'icon' => 'grid'],
            ['key' => 'cart', 'label' => 'Cart', 'icon' => 'shopping-bag'],
            ['key' => 'account', 'label' => 'Account', 'icon' => 'user'],
        ];

        if (! empty($features['wishlist'])) {
            array_splice($tabs, 3, 0, [['key' => 'wishlist', 'label' => 'Wishlist', 'icon' => 'heart']]);
        }

        return ['tabs' => $tabs];
    }

    private function generateBundleId(string $storeName, string $platform): string
    {
        $slug = Str::slug($storeName, '');
        $prefix = $platform === 'ios' ? 'com.oliv.' : 'com.oliv.';

        return $prefix . Str::limit($slug, 30, '');
    }
}
```

---

## Eloquent Models

### `App\Models\OnboardingSession`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnboardingSession extends Model
{
    protected $fillable = [
        'tenant_id', 'platform', 'step', 'shopify_domain',
        'imported_data', 'wizard_payload', 'ai_recommended', 'completed_at',
    ];

    protected $casts = [
        'imported_data' => 'array',
        'wizard_payload' => 'array',
        'ai_recommended' => 'array',
        'completed_at' => 'datetime',
        'step' => 'integer',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function isComplete(): bool
    {
        return $this->completed_at !== null;
    }

    public function isImportReady(): bool
    {
        return ! empty($this->imported_data) && ! isset($this->imported_data['error']);
    }
}
```

### `App\Models\AppConfig`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppConfig extends Model
{
    protected $table = 'app_configs';

    protected $fillable = [
        'tenant_id', 'app_name', 'app_icon_url', 'splash_url',
        'primary_color', 'secondary_color', 'font_family', 'theme_id',
        'home_layout', 'navigation', 'publish_status',
        'bundle_id_ios', 'bundle_id_android',
    ];

    protected $casts = [
        'home_layout' => 'array',
        'navigation' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function theme(): BelongsTo
    {
        return $this->belongsTo(AppTheme::class, 'theme_id');
    }
}
```

### `App\Models\FeatureFlag`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeatureFlag extends Model
{
    protected $fillable = ['tenant_id', 'feature_key', 'enabled', 'config'];

    protected $casts = [
        'enabled' => 'boolean',
        'config' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
```

### `App\Models\AppTheme`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppTheme extends Model
{
    protected $fillable = ['name', 'default_config'];

    protected $casts = [
        'default_config' => 'array',
    ];
}
```

---

## Error Handling

| Scenario | Handling |
|----------|----------|
| HMAC validation fails | Return HTTP 401, display "Invalid callback" error on wizard |
| State mismatch | Return HTTP 401, display "Session expired, try again" |
| Invalid shop domain | Return HTTP 401, display "Invalid Shopify store" |
| Missing OAuth scopes | Display error with missing scopes, offer "Re-authorize" button |
| Token exchange failure | Display "Connection failed", offer retry |
| Import job fails (3 retries) | Mark session with error, show "Import failed" + manual entry fallback |
| Import job timeout | 30s per HTTP call, retries on timeout |
| AppConfig generation failure | Transaction rollback, display "Generation failed, try again" |
| Concurrent session access | First-or-create pattern prevents duplicates |

---

## Testing Strategy

### Unit Tests (PHPUnit 11)
- OAuth validation methods (HMAC, state, domain, scopes)
- AI recommendation engine scoring logic
- AppConfigGenerator output correctness
- ManualAdapter return structure
- OnboardingSession state transitions

### Property Tests (Eris / PHPUnit)
- Session payload round-trip persistence (100+ iterations with random payloads)
- Domain validation against random strings
- HMAC rejection of tampered payloads
- Scope validation with random subsets
- Encryption round-trip with random token strings
- AI engine produces valid output for random industry data

### Integration Tests
- OAuth flow end-to-end (mocked Shopify HTTP)
- Import job execution with mocked adapter
- Full wizard step progression
- AppConfig generation from complete session

---

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system — essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: OnboardingSession payload persistence round-trip

*For any* valid wizard payload data (platform, store data, branding, features, theme ID, step number), writing it to an OnboardingSession and reading it back SHALL produce identical values.

**Validates: Requirements 1.4, 3.2, 4.4, 5.5, 6.4, 7.4, 10.1**

### Property 2: HMAC validation rejects tampered signatures

*For any* OAuth callback parameter set, if the HMAC is computed with a different secret or the payload is modified after signing, the `validateHmac` method SHALL throw an `OAuthValidationException`.

**Validates: Requirements 2.2, 2.3**

### Property 3: State parameter validation rejects mismatches

*For any* two distinct random strings used as state parameters, if the callback state does not equal the session-stored state, the `validateState` method SHALL throw an `OAuthValidationException`.

**Validates: Requirements 2.4**

### Property 4: Shop domain validation accepts only valid Shopify domains

*For any* string input, `validateShopDomain` SHALL return true only when the string matches the pattern `^[a-zA-Z0-9][a-zA-Z0-9\-]*\.myshopify\.com$` and SHALL throw for all other inputs.

**Validates: Requirements 2.5**

### Property 5: Token encryption round-trip preserves the original value

*For any* non-empty access token string, encrypting with `Crypt::encryptString()` and then decrypting with `Crypt::decryptString()` SHALL produce the original token.

**Validates: Requirements 2.7**

### Property 6: Token exclusion from serialized Tenant output

*For any* Tenant with a non-null `shopify_admin_token`, calling `toArray()` or `toJson()` SHALL NOT include the raw token value or the `shopify_client_secret` value in the output.

**Validates: Requirements 2.8**

### Property 7: OAuth scope validation rejects incomplete scope sets

*For any* subset of the required scopes that is missing at least one required scope, `validateScopes` SHALL throw an `OAuthValidationException`. For any superset or exact match of the required scopes, it SHALL return true.

**Validates: Requirements 2.10**

### Property 8: Data mapping from import to wizard preserves all fields

*For any* valid imported data structure (containing store_info with name, branding with logo_url and colors), the wizard pre-fill logic SHALL map every non-null imported value to its corresponding form field without data loss.

**Validates: Requirements 4.1, 5.1, 5.2, 5.3**

### Property 9: Complete Shopify import requires zero manual fields

*For any* OnboardingSession with complete `imported_data` (non-null store_info.name, branding fields populated), the Step 3 validation SHALL pass without any additional user input.

**Validates: Requirements 4.3**

### Property 10: AI recommendation engine produces valid output for any industry

*For any* valid imported data (with or without an industry field), `generateRecommendations` SHALL produce a non-empty features map where every key is a recognized feature key, and SHALL select exactly one theme ID that exists in the `app_themes` table.

**Validates: Requirements 6.1, 6.2, 7.2**

### Property 11: App generation creates one FeatureFlag per selected feature

*For any* set of N feature selections (where each key maps to a boolean), calling `AppConfigGenerator::generate` SHALL create exactly N FeatureFlag records for the tenant, each with the correct `enabled` value, and SHALL set the session's `completed_at` to a non-null timestamp.

**Validates: Requirements 8.3, 8.5**

### Property 12: Review summary contains all configured sections

*For any* completed wizard payload containing platform, branding (app_name, colors), features, and theme_id, the summary view data SHALL include all four configuration sections with their respective values.

**Validates: Requirements 8.1**

### Property 13: Wizard resumability restores correct step

*For any* OnboardingSession with `step` set to value N (1 ≤ N ≤ 8) and `completed_at` = null, loading the wizard SHALL initialize `currentStep` to N.

**Validates: Requirements 10.2**

### Property 14: ManualAdapter returns structurally valid defaults

*For any* Tenant, calling `ManualAdapter::fetchStoreInfo`, `fetchCatalog`, and `fetchBranding` SHALL each return arrays matching the StoreAdapter interface return types with no null constraint violations — specifically: store_info contains a `name` string key, catalog contains `product_count` integer, and branding contains all four expected keys.

**Validates: Requirements 11.3**

<?php

namespace App\Services\Shopify;

use App\Models\Tenant;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Handles the platform-level Shopify OAuth (authorization-code) install flow used
 * during tenant onboarding. One Shopify app (client_id/secret in config) is shared
 * by all tenants; each tenant receives its own offline admin token, from which we
 * then mint a Storefront access token.
 */
class ShopifyOAuthService
{
    /**
     * Normalize and validate a merchant-entered shop domain.
     *
     * Accepts "mystore", "mystore.myshopify.com" or a full URL and returns the
     * canonical "mystore.myshopify.com", or null if it is not a valid shop domain.
     */
    public function normalizeShopDomain(string $input): ?string
    {
        $shop = trim(strtolower($input));

        // Strip scheme and any path/query.
        $shop = preg_replace('#^https?://#', '', $shop);
        $shop = explode('/', $shop)[0];

        // Bare handle -> append the myshopify suffix.
        if ($shop !== '' && ! str_contains($shop, '.')) {
            $shop .= '.myshopify.com';
        }

        // Only permit canonical myshopify.com subdomains to avoid open-redirect / SSRF.
        if (! preg_match('/^[a-z0-9][a-z0-9-]*\.myshopify\.com$/', $shop)) {
            return null;
        }

        return $shop;
    }

    /**
     * Build the Shopify authorization URL the merchant is redirected to.
     */
    public function buildAuthorizeUrl(string $shop, string $state, string $redirectUri): string
    {
        $query = http_build_query([
            'client_id' => config('services.shopify.client_id'),
            'scope' => config('services.shopify.scopes'),
            'redirect_uri' => $redirectUri,
            'state' => $state,
            // No grant_options[]=per-user -> offline (permanent) token.
        ]);

        return "https://{$shop}/admin/oauth/authorize?{$query}";
    }

    /**
     * Verify the HMAC signature Shopify appends to the OAuth callback.
     */
    public function verifyHmac(array $params): bool
    {
        $hmac = $params['hmac'] ?? null;
        if (! is_string($hmac) || $hmac === '') {
            return false;
        }

        unset($params['hmac'], $params['signature']);
        ksort($params);

        $computed = hash_hmac(
            'sha256',
            urldecode(http_build_query($params)),
            (string) config('services.shopify.client_secret')
        );

        return hash_equals($computed, $hmac);
    }

    /**
     * Exchange an authorization code for an offline admin API access token.
     *
     * @return array{access_token:string, scope:string, expires_in:?int, refresh_token:?string}|null
     */
    public function exchangeCodeForToken(string $shop, string $code): ?array
    {
        try {
            $response = Http::asForm()->post("https://{$shop}/admin/oauth/access_token", [
                'client_id' => config('services.shopify.client_id'),
                'client_secret' => config('services.shopify.client_secret'),
                'code' => $code,
                // Shopify requires expiring offline tokens for public apps (April 2026+).
                // This returns access_token (~1h) + a refresh_token (~90d).
                'expiring' => 1,
            ]);

            if (! $response->successful() || ! $response->json('access_token')) {
                Log::error('Shopify OAuth token exchange failed', [
                    'shop' => $shop,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            return [
                'access_token' => $response->json('access_token'),
                'scope' => $response->json('scope') ?? '',
                'expires_in' => $response->json('expires_in'),
                'refresh_token' => $response->json('refresh_token'),
            ];
        } catch (\Throwable $e) {
            Log::error('Shopify OAuth token exchange exception', [
                'shop' => $shop,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Refresh an expiring offline admin token using its refresh token.
     *
     * @return array{access_token:string, scope:string, expires_in:?int, refresh_token:?string}|null
     */
    public function refreshAccessToken(string $shop, string $refreshToken): ?array
    {
        try {
            $response = Http::asForm()->post("https://{$shop}/admin/oauth/access_token", [
                'client_id' => config('services.shopify.client_id'),
                'client_secret' => config('services.shopify.client_secret'),
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
            ]);

            if (! $response->successful() || ! $response->json('access_token')) {
                Log::error('Shopify OAuth token refresh failed', [
                    'shop' => $shop,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            return [
                'access_token' => $response->json('access_token'),
                'scope' => $response->json('scope') ?? '',
                'expires_in' => $response->json('expires_in'),
                'refresh_token' => $response->json('refresh_token'),
            ];
        } catch (\Throwable $e) {
            Log::error('Shopify OAuth token refresh exception', [
                'shop' => $shop,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Create a Storefront API access token using a freshly-obtained admin token.
     */
    public function createStorefrontToken(string $shop, string $adminToken, string $title = 'YottoCode Mobile App'): ?string
    {
        $apiVersion = config('services.shopify.api_version', '2025-10');

        $mutation = <<<'GRAPHQL'
            mutation storefrontAccessTokenCreate($input: StorefrontAccessTokenInput!) {
                storefrontAccessTokenCreate(input: $input) {
                    storefrontAccessToken { accessToken }
                    userErrors { field message }
                }
            }
        GRAPHQL;

        try {
            $response = Http::withHeaders([
                'X-Shopify-Access-Token' => $adminToken,
                'Content-Type' => 'application/json',
            ])->post("https://{$shop}/admin/api/{$apiVersion}/graphql.json", [
                'query' => $mutation,
                'variables' => ['input' => ['title' => $title]],
            ]);

            $token = $response->json('data.storefrontAccessTokenCreate.storefrontAccessToken.accessToken');
            $errors = $response->json('data.storefrontAccessTokenCreate.userErrors') ?? [];

            if (! $token || ! empty($errors)) {
                Log::error('Shopify storefront token creation failed', [
                    'shop' => $shop,
                    'status' => $response->status(),
                    'errors' => $errors,
                    'body' => $response->body(),
                ]);

                return null;
            }

            return $token;
        } catch (\Throwable $e) {
            Log::error('Shopify storefront token creation exception', [
                'shop' => $shop,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Persist the obtained Shopify credentials onto the tenant.
     *
     * @param  array{access_token:string, scope:string, expires_in:?int, refresh_token:?string}  $tokens
     */
    public function storeCredentials(Tenant $tenant, string $shop, array $tokens, ?string $storefrontToken): void
    {
        $tenant->update([
            'shopify_shop_domain' => $shop,
            'shopify_admin_token' => $tokens['access_token'],
            'shopify_admin_token_expires_at' => isset($tokens['expires_in'])
                ? now()->addSeconds((int) $tokens['expires_in'])
                : null,
            'shopify_admin_refresh_token' => $tokens['refresh_token'] ?? $tenant->shopify_admin_refresh_token,
            'shopify_storefront_token' => $storefrontToken ?? $tenant->shopify_storefront_token,
        ]);
    }
}

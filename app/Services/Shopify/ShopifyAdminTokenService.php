<?php

namespace App\Services\Shopify;

use App\Models\Tenant;
use Illuminate\Support\Facades\Log;

class ShopifyAdminTokenService
{
    public function __construct(
        private ShopifyOAuthService $oauth
    ) {}

    /**
     * Get a valid Admin API access token for the tenant.
     *
     * Expiring offline tokens (Shopify's required model for public apps) last ~1 hour
     * and are refreshed with the stored refresh token. Tokens without an expiry are
     * treated as static (legacy) and returned as-is.
     */
    public function getValidToken(Tenant $tenant): ?string
    {
        // Expiring token: refresh when missing or within 5 minutes of expiry.
        if ($tenant->shopify_admin_refresh_token && $tenant->shopify_admin_token_expires_at) {
            $stillValid = $tenant->shopify_admin_token
                && $tenant->shopify_admin_token_expires_at->greaterThan(now()->addMinutes(5));

            return $stillValid ? $tenant->shopify_admin_token : $this->refreshToken($tenant);
        }

        // Legacy / static token (no expiry) — return as-is.
        return $tenant->shopify_admin_token;
    }

    /**
     * Refresh an expiring offline token using its refresh token.
     */
    protected function refreshToken(Tenant $tenant): ?string
    {
        if (! $tenant->shopify_shop_domain || ! $tenant->shopify_admin_refresh_token) {
            Log::error('Cannot refresh Shopify token: missing shop domain or refresh token', [
                'tenant_id' => $tenant->id,
            ]);

            return null;
        }

        $tokens = $this->oauth->refreshAccessToken(
            $tenant->shopify_shop_domain,
            $tenant->shopify_admin_refresh_token
        );

        if (! $tokens) {
            return null;
        }

        $this->oauth->storeCredentials($tenant, $tenant->shopify_shop_domain, $tokens, null);

        Log::info('Shopify Admin API token refreshed', [
            'tenant_id' => $tenant->id,
            'expires_at' => $tenant->shopify_admin_token_expires_at,
        ]);

        return $tokens['access_token'];
    }
}

<?php

namespace App\Services\Shopify;

use App\Models\Tenant;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShopifyAdminTokenService
{
    /**
     * Get a valid Admin API access token for the tenant.
     * Refreshes the token if expired or about to expire (within 5 minutes).
     */
    public function getValidToken(Tenant $tenant): ?string
    {
        // If we have OAuth credentials, use token refresh flow
        if ($tenant->shopify_client_id && $tenant->shopify_client_secret) {
            return $this->getOrRefreshOAuthToken($tenant);
        }

        // Fall back to static token (for legacy/permanent tokens)
        return $tenant->shopify_admin_token;
    }

    /**
     * Get or refresh OAuth token.
     */
    protected function getOrRefreshOAuthToken(Tenant $tenant): ?string
    {
        // Check if current token is still valid (with 3 hour buffer)
        if ($tenant->shopify_admin_token && $tenant->shopify_admin_token_expires_at) {
            $expiresAt = $tenant->shopify_admin_token_expires_at;
            $bufferTime = now()->addHours(3);

            if ($expiresAt->greaterThan($bufferTime)) {
                return $tenant->shopify_admin_token;
            }
        }

        // Token expired or missing, refresh it
        return $this->refreshToken($tenant);
    }

    /**
     * Refresh the OAuth access token using client credentials.
     */
    protected function refreshToken(Tenant $tenant): ?string
    {
        if (!$tenant->shopify_shop_domain) {
            Log::error('Cannot refresh Shopify token: missing shop domain', [
                'tenant_id' => $tenant->id,
            ]);
            return null;
        }

        $url = "https://{$tenant->shopify_shop_domain}/admin/oauth/access_token";

        Log::info('Refreshing Shopify Admin API token', [
            'tenant_id' => $tenant->id,
            'shop_domain' => $tenant->shopify_shop_domain,
        ]);

        try {
            $response = Http::asForm()->post($url, [
                'grant_type' => 'client_credentials',
                'client_id' => $tenant->shopify_client_id,
                'client_secret' => $tenant->shopify_client_secret,
            ]);

            if (!$response->successful()) {
                Log::error('Shopify token refresh failed', [
                    'tenant_id' => $tenant->id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }

            $data = $response->json();
            $accessToken = $data['access_token'] ?? null;
            $expiresIn = $data['expires_in'] ?? 86400; // Default 24 hours

            if (!$accessToken) {
                Log::error('Shopify token refresh returned no access_token', [
                    'tenant_id' => $tenant->id,
                    'response' => $data,
                ]);
                return null;
            }

            // Save the new token and expiration
            $tenant->update([
                'shopify_admin_token' => $accessToken,
                'shopify_admin_token_expires_at' => now()->addSeconds($expiresIn),
            ]);

            Log::info('Shopify Admin API token refreshed successfully', [
                'tenant_id' => $tenant->id,
                'expires_in' => $expiresIn,
                'expires_at' => $tenant->shopify_admin_token_expires_at,
            ]);

            return $accessToken;

        } catch (\Throwable $e) {
            Log::error('Shopify token refresh exception', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}

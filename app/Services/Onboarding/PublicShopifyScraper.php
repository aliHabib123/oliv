<?php

namespace App\Services\Onboarding;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class PublicShopifyScraper
{
    /**
     * Fetch all publicly available data from a Shopify store.
     * Accepts either .myshopify.com domain or custom domain.
     */
    public function scrape(string $domain): array
    {
        $domain = $this->normalizeDomain($domain);

        // Verify it's a Shopify store by checking meta.json
        $metaResponse = Http::timeout(10)->get("https://{$domain}/meta.json");

        if (! $metaResponse->successful()) {
            throw new RuntimeException("Could not connect to store: {$domain}");
        }

        $meta = $metaResponse->json();

        if (empty($meta) || (! isset($meta['name']) && ! isset($meta['id']))) {
            throw new RuntimeException("This doesn't appear to be a Shopify store.");
        }

        $storeInfo = $this->buildStoreInfo($meta, $domain);
        $catalog = $this->fetchCatalog($domain);
        $branding = $this->fetchBranding($domain);

        return [
            'store_info' => $storeInfo,
            'catalog' => $catalog,
            'branding' => $branding,
            'imported_at' => now()->toISOString(),
        ];
    }

    /**
     * Build store info from meta.json response.
     */
    private function buildStoreInfo(array $meta, string $domain): array
    {
        return [
            'name' => $meta['name'] ?? '',
            'description' => $meta['description'] ?? null,
            'industry' => null,
            'email' => null,
            'phone' => null,
            'address' => null,
            'currency' => $meta['currency'] ?? null,
            'country' => $meta['country'] ?? null,
            'domain' => $domain,
            'myshopify_domain' => $meta['myshopify_domain'] ?? $domain,
        ];
    }

    /**
     * Fetch products and collections from public JSON endpoints.
     */
    private function fetchCatalog(string $domain): array
    {
        $productCount = 0;
        $sampleProducts = [];
        $categories = [];

        // Fetch products (paginate to get count)
        try {
            $page = 1;
            $totalProducts = [];

            while ($page <= 10) { // Max 10 pages (2500 products)
                $response = Http::timeout(15)->get("https://{$domain}/products.json", [
                    'limit' => 250,
                    'page' => $page,
                ]);

                if (! $response->successful()) {
                    break;
                }

                $products = $response->json('products') ?? [];

                if (empty($products)) {
                    break;
                }

                $totalProducts = array_merge($totalProducts, $products);
                $page++;

                if (count($products) < 250) {
                    break;
                }
            }

            $productCount = count($totalProducts);
            $sampleProducts = collect($totalProducts)->take(10)->map(fn($p) => [
                'title' => $p['title'] ?? '',
                'type' => $p['product_type'] ?? null,
                'image' => $p['images'][0]['src'] ?? null,
                'price' => $p['variants'][0]['price'] ?? null,
                'handle' => $p['handle'] ?? null,
            ])->all();
        } catch (\Exception) {
            // Products endpoint may be disabled
        }

        // Fetch collections
        try {
            $response = Http::timeout(15)->get("https://{$domain}/collections.json");

            if ($response->successful()) {
                $collections = $response->json('collections') ?? [];
                $categories = collect($collections)->map(fn($c) => [
                    'title' => $c['title'] ?? '',
                    'handle' => $c['handle'] ?? '',
                    'image' => $c['image']['src'] ?? null,
                ])->all();
            }
        } catch (\Exception) {
            // Collections endpoint may be disabled
        }

        return [
            'product_count' => $productCount,
            'categories' => $categories,
            'category_count' => count($categories),
            'sample_products' => $sampleProducts,
        ];
    }

    /**
     * Fetch branding (logo, colors) from the storefront.
     */
    private function fetchBranding(string $domain): array
    {
        $logo = null;
        $primaryColor = null;
        $secondaryColor = null;
        $favicon = null;

        try {
            $response = Http::timeout(10)->get("https://{$domain}");
            $html = $response->body();

            // Extract logo - multiple strategies
            $logo = $this->extractLogo($html);

            // Extract favicon
            if (preg_match('/<link[^>]*rel="[^"]*icon[^"]*"[^>]*href="([^"]+)"/i', $html, $matches)) {
                $favicon = $this->fixUrl($matches[1]);
            }

            // Extract colors from CSS variables (Shopify Dawn theme pattern)
            $primaryColor = $this->extractColor($html, [
                '--color-base-accent-1',
                '--color-primary',
                '--primary-color',
                '--accent-color',
            ]);

            $secondaryColor = $this->extractColor($html, [
                '--color-base-accent-2',
                '--color-secondary',
                '--secondary-color',
            ]);

            // Try to extract from RGB values if hex not found
            if (! $primaryColor) {
                $primaryColor = $this->extractRgbColor($html, '--color-base-accent-1');
            }

        } catch (\Exception) {
            // Branding extraction is best-effort
        }

        return [
            'logo_url' => $logo ?? $favicon,
            'favicon_url' => $favicon,
            'primary_color' => $primaryColor,
            'secondary_color' => $secondaryColor,
            'font_family' => null,
        ];
    }

    /**
     * Extract logo URL using multiple detection strategies.
     */
    private function extractLogo(string $html): ?string
    {
        // Strategy 1: img with logo in class
        if (preg_match('/<img[^>]*class="[^"]*logo[^"]*"[^>]*src="([^"]+)"/i', $html, $m)) {
            return $this->fixUrl($m[1]);
        }

        // Strategy 2: img with logo in alt
        if (preg_match('/<img[^>]*alt="[^"]*logo[^"]*"[^>]*src="([^"]+)"/i', $html, $m)) {
            return $this->fixUrl($m[1]);
        }

        // Strategy 3: img inside header or .header
        if (preg_match('/<header[^>]*>.*?<img[^>]*src="([^"]+)"/si', $html, $m)) {
            return $this->fixUrl($m[1]);
        }

        // Strategy 4: og:image meta tag
        if (preg_match('/<meta[^>]*property="og:image"[^>]*content="([^"]+)"/i', $html, $m)) {
            return $this->fixUrl($m[1]);
        }

        // Strategy 5: src with logo in the filename
        if (preg_match('/src="([^"]*logo[^"]*)"/i', $html, $m)) {
            return $this->fixUrl($m[1]);
        }

        return null;
    }

    /**
     * Extract a color value from CSS variables.
     */
    private function extractColor(string $html, array $variableNames): ?string
    {
        foreach ($variableNames as $var) {
            $escapedVar = preg_quote($var, '/');
            if (preg_match("/{$escapedVar}:\s*(#[0-9a-fA-F]{3,8})/i", $html, $m)) {
                return $m[1];
            }
        }
        return null;
    }

    /**
     * Extract color from RGB CSS variable (Shopify stores often use RGB values).
     */
    private function extractRgbColor(string $html, string $variable): ?string
    {
        $escaped = preg_quote($variable, '/');
        if (preg_match("/{$escaped}:\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)/i", $html, $m)) {
            return sprintf('#%02x%02x%02x', (int) $m[1], (int) $m[2], (int) $m[3]);
        }
        return null;
    }

    /**
     * Fix protocol-relative and relative URLs.
     */
    private function fixUrl(string $url): string
    {
        if (str_starts_with($url, '//')) {
            $url = 'https:' . $url;
        }

        // Clean HTML entities
        $url = html_entity_decode($url, ENT_QUOTES, 'UTF-8');

        // Limit Shopify CDN images to 256px for icon usage
        if (str_contains($url, 'cdn.shopify.com') && str_contains($url, 'width=')) {
            $url = preg_replace('/width=\d+/', 'width=256', $url);
        }

        return $url;
    }

    /**
     * Normalize the domain input. Handles:
     * - just store name: "my-store" → "my-store.myshopify.com"
     * - full myshopify URL: "my-store.myshopify.com" → as-is
     * - custom domain: "mystore.com" → as-is
     * - with protocol: "https://mystore.com" → "mystore.com"
     */
    private function normalizeDomain(string $domain): string
    {
        $domain = trim(strtolower($domain));

        // Remove protocol
        $domain = preg_replace('#^https?://#', '', $domain);

        // Remove trailing slash and path
        $domain = explode('/', $domain)[0];

        // If no dots at all, assume it's just the store name
        if (! str_contains($domain, '.')) {
            $domain .= '.myshopify.com';
        }

        return $domain;
    }
}

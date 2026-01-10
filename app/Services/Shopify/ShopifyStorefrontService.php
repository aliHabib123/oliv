<?php

namespace App\Services\Shopify;

use App\Models\Tenant;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShopifyStorefrontService
{
    /**
     * Shopify Storefront API version to use.
     */
    private const API_VERSION = '2025-10';

    /**
     * Execute a GraphQL query against the tenant's Storefront API.
     *
     * @return array<string, mixed>
     */
    protected function graphql(Tenant $tenant, string $query, array $variables = []): array
    {
        $domain = $tenant->shopify_shop_domain;
        $token = $tenant->shopify_storefront_token;

        if (! $domain || ! $token) {
            Log::warning('ShopifyStorefrontService: missing domain or token for tenant', [
                'tenant_id' => $tenant->id ?? null,
            ]);

            return [];
        }

        $endpoint = sprintf('https://%s/api/%s/graphql.json', $domain, self::API_VERSION);

        try {
            $response = Http::withHeaders([
                'X-Shopify-Storefront-Access-Token' => $token,
                'Content-Type' => 'application/json',
            ])->post($endpoint, [
                'query' => $query,
                'variables' => $variables,
            ]);
        } catch (\Throwable $exception) {
            Log::error('ShopifyStorefrontService: HTTP error when calling Storefront API', [
                'tenant_id' => $tenant->id ?? null,
                'exception' => $exception,
            ]);

            return [];
        }

        if (! $response->ok()) {
            Log::warning('ShopifyStorefrontService: non-OK response from Storefront API', [
                'tenant_id' => $tenant->id ?? null,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [];
        }

        $json = $response->json();

        if (! empty($json['errors'])) {
            Log::warning('ShopifyStorefrontService: GraphQL errors returned', [
                'tenant_id' => $tenant->id ?? null,
                'errors' => $json['errors'],
            ]);

            return [];
        }

        return $json['data'] ?? [];
    }

    /**
     * Search products by a human-entered term.
     * Returns an array of lightweight product records suitable for selects.
     *
     * Each item: [
     *   'id' => string, // Shopify GID
     *   'title' => string,
     *   'handle' => string|null,
     *   'vendor' => string|null,
     *   'image_url' => string|null,
     * ]
     *
     * @return array<int, array<string, mixed>>
     */
    public function searchProducts(Tenant $tenant, string $term, int $limit = 10): array
    {
        $term = trim($term);

        if ($term === '') {
            return [];
        }

        $query = <<<'GRAPHQL'
            query SearchProducts($query: String!, $first: Int!) {
                products(first: $first, query: $query) {
                    edges {
                        node {
                            id
                            title
                            handle
                            vendor
                            featuredImage {
                                url
                            }
                        }
                    }
                }
            }
        GRAPHQL;

        // Basic search across title and vendor.
        $searchQuery = $term;

        $data = $this->graphql($tenant, $query, [
            'query' => $searchQuery,
            'first' => $limit,
        ]);

        $edges = Arr::get($data, 'products.edges', []);

        return collect($edges)
            ->map(function (array $edge): array {
                $node = $edge['node'] ?? [];

                return [
                    'id' => $node['id'] ?? null,
                    'title' => $node['title'] ?? '',
                    'handle' => $node['handle'] ?? null,
                    'vendor' => $node['vendor'] ?? null,
                    'image_url' => Arr::get($node, 'featuredImage.url'),
                ];
            })
            ->filter(fn (array $product): bool => ! empty($product['id']))
            ->values()
            ->all();
    }

    /**
     * Search collections to use as "categories".
     *
     * Each item: [
     *   'id' => string, // Shopify GID
     *   'title' => string,
     *   'handle' => string|null,
     *   'image_url' => string|null,
     * ]
     *
     * @return array<int, array<string, mixed>>
     */
    public function searchCollections(Tenant $tenant, string $term, int $limit = 10): array
    {
        $term = trim($term);

        if ($term === '') {
            return [];
        }

        $query = <<<'GRAPHQL'
            query SearchCollections($query: String!, $first: Int!) {
                collections(first: $first, query: $query) {
                    edges {
                        node {
                            id
                            title
                            handle
                            image {
                                url
                            }
                        }
                    }
                }
            }
        GRAPHQL;

        $data = $this->graphql($tenant, $query, [
            'query' => $term,
            'first' => $limit,
        ]);

        $edges = Arr::get($data, 'collections.edges', []);

        return collect($edges)
            ->map(function (array $edge): array {
                $node = $edge['node'] ?? [];

                return [
                    'id' => $node['id'] ?? null,
                    'title' => $node['title'] ?? '',
                    'handle' => $node['handle'] ?? null,
                    'image_url' => Arr::get($node, 'image.url'),
                ];
            })
            ->filter(fn (array $collection): bool => ! empty($collection['id']))
            ->values()
            ->all();
    }

    /**
     * Get a single product by its Shopify GID.
     *
     * Returns: [
     *   'id' => string,
     *   'title' => string,
     *   'handle' => string|null,
     *   'vendor' => string|null,
     *   'image_url' => string|null,
     * ] or null if not found.
     *
     * @return array<string, mixed>|null
     */
    public function getProductById(Tenant $tenant, string $productId): ?array
    {
        if (! $productId) {
            return null;
        }

        $query = <<<'GRAPHQL'
            query GetProduct($id: ID!) {
                product(id: $id) {
                    id
                    title
                    handle
                    vendor
                    featuredImage {
                        url
                    }
                }
            }
        GRAPHQL;

        $data = $this->graphql($tenant, $query, [
            'id' => $productId,
        ]);

        $product = Arr::get($data, 'product');

        if (! $product || empty($product['id'])) {
            return null;
        }

        return [
            'id' => $product['id'],
            'title' => $product['title'] ?? '',
            'handle' => $product['handle'] ?? null,
            'vendor' => $product['vendor'] ?? null,
            'image_url' => Arr::get($product, 'featuredImage.url'),
        ];
    }

    /**
     * Get a single collection by its Shopify GID.
     *
     * Returns: [
     *   'id' => string,
     *   'title' => string,
     *   'handle' => string|null,
     *   'image_url' => string|null,
     * ] or null if not found.
     *
     * @return array<string, mixed>|null
     */
    public function getCollectionById(Tenant $tenant, string $collectionId): ?array
    {
        if (! $collectionId) {
            return null;
        }

        $query = <<<'GRAPHQL'
            query GetCollection($id: ID!) {
                collection(id: $id) {
                    id
                    title
                    handle
                    image {
                        url
                    }
                }
            }
        GRAPHQL;

        $data = $this->graphql($tenant, $query, [
            'id' => $collectionId,
        ]);

        $collection = Arr::get($data, 'collection');

        if (! $collection || empty($collection['id'])) {
            return null;
        }

        return [
            'id' => $collection['id'],
            'title' => $collection['title'] ?? '',
            'handle' => $collection['handle'] ?? null,
            'image_url' => Arr::get($collection, 'image.url'),
        ];
    }

    /**
     * Search brands based on product.vendor.
     *
     * Each item: [
     *   'name' => string,
     * ]
     *
     * @return array<int, array<string, string>>
     */
    public function searchBrands(Tenant $tenant, string $term, int $limit = 10): array
    {
        $term = trim($term);

        if ($term === '') {
            return [];
        }

        // Use product search with a vendor-focused query.
        $query = <<<'GRAPHQL'
            query SearchBrands($query: String!, $first: Int!) {
                products(first: $first, query: $query) {
                    edges {
                        node {
                            vendor
                        }
                    }
                }
            }
        GRAPHQL;

        // Shopify search syntax supports vendor:"Vendor Name" and prefix matches like vendor:Vendor*.
        $searchQuery = sprintf('vendor:%s*', $term);

        $data = $this->graphql($tenant, $query, [
            'query' => $searchQuery,
            'first' => max($limit * 2, 20), // fetch a few more to dedupe
        ]);

        $edges = Arr::get($data, 'products.edges', []);

        $vendors = collect($edges)
            ->map(fn (array $edge): ?string => $edge['node']['vendor'] ?? null)
            ->filter()
            ->unique()
            ->sort()
            ->take($limit)
            ->values()
            ->all();

        return array_map(fn (string $name): array => ['name' => $name], $vendors);
    }
}

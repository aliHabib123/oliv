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
    protected function graphql(Tenant $tenant, string $query, array $variables = [], ?string $customerToken = null): array
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

        $headers = [
            'X-Shopify-Storefront-Access-Token' => $token,
            'Content-Type' => 'application/json',
        ];

        if ($customerToken) {
            $headers['X-Shopify-Customer-Access-Token'] = $customerToken;
        }

        try {
            $response = Http::withHeaders($headers)->post($endpoint, [
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
                    description
                    descriptionHtml
                    featuredImage {
                        url
                    }
                    images(first: 10) {
                        edges {
                            node {
                                url
                                altText
                            }
                        }
                    }
                    priceRange {
                        minVariantPrice {
                            amount
                            currencyCode
                        }
                        maxVariantPrice {
                            amount
                            currencyCode
                        }
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

        $images = [];
        foreach (Arr::get($product, 'images.edges', []) as $edge) {
            $images[] = [
                'url' => $edge['node']['url'] ?? null,
                'altText' => $edge['node']['altText'] ?? null,
            ];
        }

        return [
            'id' => $product['id'],
            'title' => $product['title'] ?? '',
            'handle' => $product['handle'] ?? null,
            'vendor' => $product['vendor'] ?? null,
            'description' => $product['description'] ?? null,
            'descriptionHtml' => $product['descriptionHtml'] ?? null,
            'image_url' => Arr::get($product, 'featuredImage.url'),
            'images' => $images,
            'priceRange' => $product['priceRange'] ?? null,
        ];
    }

    /**
     * Get latest products from Shopify.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getLatestProducts(Tenant $tenant, int $limit = 10): array
    {
        $query = <<<'GRAPHQL'
            query GetLatestProducts($first: Int!) {
                products(first: $first, sortKey: CREATED_AT, reverse: true) {
                    edges {
                        node {
                            id
                            title
                            handle
                            vendor
                            description
                            descriptionHtml
                            featuredImage {
                                url
                            }
                            images(first: 10) {
                                edges {
                                    node {
                                        url
                                        altText
                                    }
                                }
                            }
                            priceRange {
                                minVariantPrice {
                                    amount
                                    currencyCode
                                }
                                maxVariantPrice {
                                    amount
                                    currencyCode
                                }
                            }
                        }
                    }
                }
            }
        GRAPHQL;

        $data = $this->graphql($tenant, $query, [
            'first' => $limit,
        ]);

        $edges = Arr::get($data, 'products.edges', []);

        return collect($edges)
            ->map(function (array $edge): array {
                $node = $edge['node'] ?? [];

                $images = [];
                foreach (Arr::get($node, 'images.edges', []) as $imgEdge) {
                    $images[] = [
                        'url' => $imgEdge['node']['url'] ?? null,
                        'altText' => $imgEdge['node']['altText'] ?? null,
                    ];
                }

                return [
                    'id' => $node['id'] ?? null,
                    'title' => $node['title'] ?? '',
                    'handle' => $node['handle'] ?? null,
                    'vendor' => $node['vendor'] ?? null,
                    'description' => $node['description'] ?? null,
                    'descriptionHtml' => $node['descriptionHtml'] ?? null,
                    'image_url' => Arr::get($node, 'featuredImage.url'),
                    'images' => $images,
                    'priceRange' => $node['priceRange'] ?? null,
                ];
            })
            ->filter(fn (array $product): bool => !empty($product['id']))
            ->values()
            ->all();
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

    /**
     * Create a new cart in Shopify.
     *
     * @return array<string, mixed>
     */
    public function createCart(Tenant $tenant, ?string $customerToken = null): array
    {
        $query = <<<'GRAPHQL'
            mutation cartCreate {
                cartCreate {
                    cart {
                        id
                        lines(first: 100) {
                            edges {
                                node {
                                    id
                                    quantity
                                    merchandise {
                                        ... on ProductVariant {
                                            id
                                            title
                                            priceV2 {
                                                amount
                                                currencyCode
                                            }
                                            compareAtPriceV2 {
                                                amount
                                                currencyCode
                                            }
                                            quantityAvailable
                                            selectedOptions {
                                                name
                                                value
                                            }
                                            image {
                                                url
                                                altText
                                            }
                                            product {
                                                id
                                                title
                                                description
                                                options {
                                                    name
                                                    values
                                                }
                                                featuredImage {
                                                    url
                                                    altText
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        estimatedCost {
                            totalAmount {
                                amount
                                currencyCode
                            }
                        }
                    }
                }
            }
        GRAPHQL;

        $data = $this->graphql($tenant, $query, [], $customerToken);

        return Arr::get($data, 'cartCreate.cart', []);
    }

    /**
     * Add a line item to cart.
     *
     * @return array<string, mixed>
     */
    public function addCartLine(Tenant $tenant, string $cartId, string $variantId, int $quantity, ?string $customerToken = null): array
    {
        $query = <<<'GRAPHQL'
            mutation cartLinesAdd($cartId: ID!, $lines: [CartLineInput!]!) {
                cartLinesAdd(cartId: $cartId, lines: $lines) {
                    cart {
                        id
                        lines(first: 100) {
                            edges {
                                node {
                                    id
                                    quantity
                                    merchandise {
                                        ... on ProductVariant {
                                            id
                                            title
                                            priceV2 {
                                                amount
                                                currencyCode
                                            }
                                            compareAtPriceV2 {
                                                amount
                                                currencyCode
                                            }
                                            quantityAvailable
                                            selectedOptions {
                                                name
                                                value
                                            }
                                            image {
                                                url
                                                altText
                                            }
                                            product {
                                                id
                                                title
                                                description
                                                options {
                                                    name
                                                    values
                                                }
                                                featuredImage {
                                                    url
                                                    altText
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        estimatedCost {
                            totalAmount {
                                amount
                                currencyCode
                            }
                        }
                    }
                }
            }
        GRAPHQL;

        $data = $this->graphql($tenant, $query, [
            'cartId' => $cartId,
            'lines' => [
                [
                    'merchandiseId' => $variantId,
                    'quantity' => $quantity,
                ],
            ],
        ], $customerToken);

        return Arr::get($data, 'cartLinesAdd.cart', []);
    }

    /**
     * Remove a line item from cart.
     *
     * @return array<string, mixed>
     */
    public function removeCartLine(Tenant $tenant, string $cartId, string $lineId, ?string $customerToken = null): array
    {
        $query = <<<'GRAPHQL'
            mutation cartLinesRemove($cartId: ID!, $lineIds: [ID!]!) {
                cartLinesRemove(cartId: $cartId, lineIds: $lineIds) {
                    cart {
                        id
                        lines(first: 100) {
                            edges {
                                node {
                                    id
                                    quantity
                                    merchandise {
                                        ... on ProductVariant {
                                            id
                                            title
                                            priceV2 {
                                                amount
                                                currencyCode
                                            }
                                            compareAtPriceV2 {
                                                amount
                                                currencyCode
                                            }
                                            quantityAvailable
                                            selectedOptions {
                                                name
                                                value
                                            }
                                            image {
                                                url
                                                altText
                                            }
                                            product {
                                                id
                                                title
                                                description
                                                options {
                                                    name
                                                    values
                                                }
                                                featuredImage {
                                                    url
                                                    altText
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        estimatedCost {
                            totalAmount {
                                amount
                                currencyCode
                            }
                        }
                    }
                }
            }
        GRAPHQL;

        $data = $this->graphql($tenant, $query, [
            'cartId' => $cartId,
            'lineIds' => [$lineId],
        ], $customerToken);

        return Arr::get($data, 'cartLinesRemove.cart', []);
    }

    /**
     * Update a cart line quantity.
     *
     * @return array<string, mixed>
     */
    public function updateCartLine(Tenant $tenant, string $cartId, string $lineId, int $quantity, ?string $customerToken = null): array
    {
        $query = <<<'GRAPHQL'
            mutation cartLinesUpdate($cartId: ID!, $lines: [CartLineUpdateInput!]!) {
                cartLinesUpdate(cartId: $cartId, lines: $lines) {
                    cart {
                        id
                        lines(first: 100) {
                            edges {
                                node {
                                    id
                                    quantity
                                    merchandise {
                                        ... on ProductVariant {
                                            id
                                            title
                                            priceV2 {
                                                amount
                                                currencyCode
                                            }
                                            compareAtPriceV2 {
                                                amount
                                                currencyCode
                                            }
                                            quantityAvailable
                                            selectedOptions {
                                                name
                                                value
                                            }
                                            image {
                                                url
                                                altText
                                            }
                                            product {
                                                id
                                                title
                                                description
                                                options {
                                                    name
                                                    values
                                                }
                                                featuredImage {
                                                    url
                                                    altText
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        estimatedCost {
                            totalAmount {
                                amount
                                currencyCode
                            }
                        }
                    }
                }
            }
        GRAPHQL;

        $data = $this->graphql($tenant, $query, [
            'cartId' => $cartId,
            'lines' => [
                [
                    'id' => $lineId,
                    'quantity' => $quantity,
                ],
            ],
        ], $customerToken);

        return Arr::get($data, 'cartLinesUpdate.cart', []);
    }

    /**
     * Get cart by ID.
     *
     * @return array<string, mixed>
     */
    public function getCart(Tenant $tenant, string $cartId, ?string $customerToken = null): array
    {
        $query = <<<'GRAPHQL'
            query getCart($cartId: ID!) {
                cart(id: $cartId) {
                    id
                    lines(first: 100) {
                        edges {
                            node {
                                id
                                quantity
                                merchandise {
                                    ... on ProductVariant {
                                        id
                                        title
                                        priceV2 {
                                            amount
                                            currencyCode
                                        }
                                        compareAtPriceV2 {
                                            amount
                                            currencyCode
                                        }
                                        quantityAvailable
                                        selectedOptions {
                                            name
                                            value
                                        }
                                        image {
                                            url
                                            altText
                                        }
                                        product {
                                            id
                                            title
                                            featuredImage {
                                                url
                                                altText
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    estimatedCost {
                        totalAmount {
                            amount
                            currencyCode
                        }
                    }
                }
            }
        GRAPHQL;

        $data = $this->graphql($tenant, $query, [
            'cartId' => $cartId,
        ], $customerToken);

        return Arr::get($data, 'cart', []);
    }

    /**
     * Create a checkout from line items using Cart API.
     * Note: checkoutCreate was deprecated in API 2023-04+, replaced by Cart API.
     *
     * @param array<int, array{variant_id: string, quantity: int}> $lineItems
     * @return array<string, mixed>
     */
    public function createCheckout(Tenant $tenant, array $lineItems, ?string $customerToken = null): array
    {
        $query = <<<'GRAPHQL'
            mutation cartCreate($input: CartInput!) {
                cartCreate(input: $input) {
                    cart {
                        id
                        checkoutUrl
                        estimatedCost {
                            subtotalAmount {
                                amount
                                currencyCode
                            }
                            totalAmount {
                                amount
                                currencyCode
                            }
                        }
                        lines(first: 100) {
                            edges {
                                node {
                                    id
                                    quantity
                                    merchandise {
                                        ... on ProductVariant {
                                            id
                                            title
                                        }
                                    }
                                }
                            }
                        }
                    }
                    userErrors {
                        message
                        field
                    }
                }
            }
        GRAPHQL;

        $lines = array_map(fn ($item) => [
            'merchandiseId' => $item['variant_id'],
            'quantity' => $item['quantity'],
        ], $lineItems);

        $data = $this->graphql($tenant, $query, [
            'input' => [
                'lines' => $lines,
            ],
        ], $customerToken);

        // Check for user errors
        $userErrors = Arr::get($data, 'cartCreate.userErrors', []);
        if (!empty($userErrors)) {
            $errorMessages = array_column($userErrors, 'message');
            throw new \Exception('Checkout creation failed: ' . implode(', ', $errorMessages));
        }

        $cart = Arr::get($data, 'cartCreate.cart', []);

        if (empty($cart) || !isset($cart['id'])) {
            throw new \Exception('Checkout creation failed: No cart returned from Shopify');
        }

        // Transform to match expected checkout response format
        return [
            'id' => $cart['id'],
            'webUrl' => $cart['checkoutUrl'],
            'email' => null,
            'subtotalPriceV2' => $cart['estimatedCost']['subtotalAmount'] ?? null,
            'totalPriceV2' => $cart['estimatedCost']['totalAmount'] ?? null,
            'lineItems' => $cart['lines'] ?? [],
        ];
    }

    /**
     * Get product with variants to retrieve default variant.
     *
     * @return array<string, mixed>|null
     */
    public function getProductWithVariants(Tenant $tenant, string $productId): ?array
    {
        $query = <<<'GRAPHQL'
            query getProduct($id: ID!) {
                product(id: $id) {
                    id
                    title
                    variants(first: 10) {
                        edges {
                            node {
                                id
                                title
                                priceV2 {
                                    amount
                                    currencyCode
                                }
                                availableForSale
                            }
                        }
                    }
                }
            }
        GRAPHQL;

        $data = $this->graphql($tenant, $query, [
            'id' => $productId,
        ]);

        $product = Arr::get($data, 'product');

        if (!$product) {
            return null;
        }

        $variants = [];
        $defaultVariant = null;

        foreach (Arr::get($product, 'variants.edges', []) as $edge) {
            $variant = $edge['node'];
            $variants[] = $variant;

            if (!$defaultVariant) {
                $defaultVariant = $variant;
            }
        }

        return [
            'id' => $product['id'],
            'title' => $product['title'],
            'default_variant' => $defaultVariant,
            'variants' => $variants,
        ];
    }

    /**
     * Get customer information using customer access token.
     *
     * @return array<string, mixed>|null
     */
    public function getCustomerInfo(Tenant $tenant, string $customerToken): ?array
    {
        $query = <<<'GRAPHQL'
            query {
                customer {
                    id
                    email
                    firstName
                    lastName
                }
            }
        GRAPHQL;

        $data = $this->graphql($tenant, $query, [], $customerToken);

        return Arr::get($data, 'customer');
    }

    /**
     * Get menu by handle from Shopify.
     *
     * @return array<string, mixed>|null
     */
    public function getMenuByHandle(Tenant $tenant, string $handle): ?array
    {
        $query = <<<'GRAPHQL'
            query GetMenu($handle: String!) {
                menu(handle: $handle) {
                    id
                    handle
                    title
                    items {
                        id
                        title
                        url
                        type
                        resourceId
                        items {
                            id
                        }
                    }
                }
            }
        GRAPHQL;

        $data = $this->graphql($tenant, $query, ['handle' => $handle]);

        return Arr::get($data, 'menu');
    }

    /**
     * Get level 0 categories from flutter-menu.
     * Returns only top-level menu items (no nested children).
     *
     * Each item: [
     *   'gid' => string,        // Shopify collection GID
     *   'handle' => string|null, // Collection handle
     *   'title' => string,      // Display name
     * ]
     *
     * @return array<int, array<string, mixed>>
     */
    public function getFlutterMenuLevel0Categories(Tenant $tenant): array
    {
        $menu = $this->getMenuByHandle($tenant, 'flutter-menu');

        if (!$menu || empty($menu['items'])) {
            return [];
        }

        // Filter only level 0 items (items without nested children or that are collections)
        $level0Items = collect($menu['items'])
            ->filter(function ($item) {
                // Must have a resourceId (Shopify GID)
                if (empty($item['resourceId'])) {
                    return false;
                }

                // Only include collection types
                if (isset($item['type']) && $item['type'] !== 'COLLECTION') {
                    return false;
                }

                return true;
            })
            ->map(function ($item) {
                return [
                    'gid' => $item['resourceId'],
                    'handle' => $this->extractHandleFromUrl($item['url'] ?? ''),
                    'title' => $item['title'] ?? '',
                ];
            })
            ->filter(fn($item) => !empty($item['gid']) && !empty($item['handle']))
            ->values()
            ->all();

        return $level0Items;
    }

    /**
     * Get shop payment settings.
     *
     * @return array<string, mixed>
     */
    public function getPaymentSettings(Tenant $tenant): array
    {
        $query = <<<'GRAPHQL'
            query GetPaymentSettings {
                shop {
                    paymentSettings {
                        acceptedCardBrands
                        cardVaultUrl
                        countryCode
                        currencyCode
                        enabledPresentmentCurrencies
                        shopifyPaymentsAccountId
                        supportedDigitalWallets
                    }
                }
            }
        GRAPHQL;

        $response = $this->graphql($tenant, $query);

        return $response['data']['shop']['paymentSettings'] ?? [];
    }

    /**
     * Extract collection handle from Shopify URL.
     *
     * @param string $url
     * @return string|null
     */
    private function extractHandleFromUrl(string $url): ?string
    {
        // Extract handle from URL like /collections/women -> women
        if (preg_match('/\/collections\/([^\/\?]+)/', $url, $matches)) {
            return $matches[1];
        }

        return null;
    }
}

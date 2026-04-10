<?php

namespace App\Services\Shopify;

use App\Models\CheckoutSession;
use App\Models\PaymentTransaction;
use App\Models\Tenant;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShopifyAdminService
{
    private const API_VERSION = '2025-10';

    public function __construct(
        private ShopifyAdminTokenService $tokenService
    ) {}

    /**
     * Create a Shopify order from a checkout session.
     *
     * @return array{order_id: string, order_name: string, order_data: array}
     * @throws \RuntimeException
     */
    public function createOrder(CheckoutSession $session): array
    {
        $tenant = $session->tenant;

        $lineItems = collect($session->line_items)->map(fn(array $item) => [
            'variantId' => $item['variant_id'],
            'quantity' => $item['quantity'],
        ])->all();

        $shippingAddress = $this->buildShopifyAddress($session->shipping_address);

        $mutation = <<<'GRAPHQL'
            mutation orderCreate($order: OrderCreateOrderInput!, $options: OrderCreateOptionsInput) {
                orderCreate(order: $order, options: $options) {
                    order {
                        id
                        name
                        totalPriceSet {
                            shopMoney { amount currencyCode }
                        }
                    }
                    userErrors { field message }
                }
            }
        GRAPHQL;

        $variables = [
            'order' => [
                'lineItems' => $lineItems,
                'shippingAddress' => $shippingAddress,
                'email' => $session->customer_email,
                'phone' => $session->customer_phone,
                'currency' => $session->currency,
                'note' => "Native checkout session: {$session->session_uuid}",
            ],
            'options' => [
                'inventoryBehaviour' => 'DECREMENT_OBEYING_POLICY',
            ],
        ];

        $data = $this->adminGraphql($tenant, $mutation, $variables);

        $errors = $data['orderCreate']['userErrors'] ?? [];
        if (! empty($errors)) {
            $errorMsg = collect($errors)->pluck('message')->implode(', ');
            Log::error('Shopify orderCreate userErrors', [
                'session_uuid' => $session->session_uuid,
                'errors' => $errors,
            ]);
            throw new \RuntimeException("Shopify orderCreate failed: {$errorMsg}");
        }

        $order = $data['orderCreate']['order'] ?? null;
        if (! $order || empty($order['id'])) {
            throw new \RuntimeException('Shopify orderCreate returned no order');
        }

        return [
            'order_id' => $order['id'],
            'order_name' => $order['name'] ?? '',
            'order_data' => $order,
        ];
    }

    /**
     * Mark a Shopify order as paid.
     *
     * @throws \RuntimeException
     */
    public function markOrderAsPaid(string $shopifyOrderId, PaymentTransaction $transaction): array
    {
        $tenant = $transaction->tenant;

        $mutation = <<<'GRAPHQL'
            mutation orderMarkAsPaid($input: OrderMarkAsPaidInput!) {
                orderMarkAsPaid(input: $input) {
                    order { id }
                    userErrors { field message }
                }
            }
        GRAPHQL;

        $variables = [
            'input' => [
                'id' => $shopifyOrderId,
            ],
        ];

        $data = $this->adminGraphql($tenant, $mutation, $variables);

        $errors = $data['orderMarkAsPaid']['userErrors'] ?? [];
        if (! empty($errors)) {
            $errorMsg = collect($errors)->pluck('message')->implode(', ');
            Log::error('Shopify orderMarkAsPaid userErrors', [
                'order_id' => $shopifyOrderId,
                'errors' => $errors,
            ]);
            throw new \RuntimeException("Shopify orderMarkAsPaid failed: {$errorMsg}");
        }

        return $data['orderMarkAsPaid']['order'] ?? [];
    }

    /**
     * Execute a GraphQL mutation against the Shopify Admin API.
     */
    protected function adminGraphql(Tenant $tenant, string $query, array $variables = []): array
    {
        $domain = $tenant->shopify_shop_domain;

        if (! $domain) {
            throw new \RuntimeException('Missing Shopify shop domain for tenant ' . $tenant->id);
        }

        // Get valid token (auto-refreshes if needed)
        $token = $this->tokenService->getValidToken($tenant);

        if (! $token) {
            throw new \RuntimeException('Unable to get valid Shopify Admin API token for tenant ' . $tenant->id);
        }

        $endpoint = sprintf('https://%s/admin/api/%s/graphql.json', $domain, self::API_VERSION);

        try {
            $response = Http::withHeaders([
                'X-Shopify-Access-Token' => $token,
                'Content-Type' => 'application/json',
            ])
                ->timeout(30)
                ->post($endpoint, [
                    'query' => $query,
                    'variables' => $variables,
                ]);
        } catch (\Throwable $e) {
            Log::error('Shopify Admin API connection error', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Shopify Admin API connection error: ' . $e->getMessage(), 0, $e);
        }

        if (! $response->ok()) {
            Log::error('Shopify Admin API non-OK response', [
                'tenant_id' => $tenant->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \RuntimeException('Shopify Admin API returned ' . $response->status());
        }

        $json = $response->json();

        if (! empty($json['errors'])) {
            Log::error('Shopify Admin GraphQL errors', [
                'tenant_id' => $tenant->id,
                'errors' => $json['errors'],
            ]);
            throw new \RuntimeException('Shopify Admin GraphQL errors: ' . json_encode($json['errors']));
        }

        return $json['data'] ?? [];
    }

    private function buildShopifyAddress(array $address): array
    {
        return [
            'firstName' => $address['first_name'] ?? '',
            'lastName' => $address['last_name'] ?? '',
            'address1' => $address['address1'] ?? '',
            'address2' => $address['address2'] ?? '',
            'city' => $address['city'] ?? '',
            'provinceCode' => $address['province_code'] ?? '',
            'countryCode' => $address['country_code'] ?? 'AE',
            'zip' => $address['zip'] ?? '',
            'phone' => $address['phone'] ?? '',
        ];
    }
}

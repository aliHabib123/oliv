<?php

namespace App\Services\Cart;

use App\Models\Cart;
use App\Models\CartEvent;
use App\Models\Tenant;
use App\Services\Shopify\ShopifyStorefrontService;
use Illuminate\Support\Facades\Http;

class CartService
{
    public function __construct(
        private ShopifyStorefrontService $shopifyService
    ) {}

    public function addToCart(
        Tenant $tenant,
        array $customerInfo,
        string $productId,
        string $variantId,
        int $quantity = 1,
        ?array $attributes = null
    ): array {
        // Get or create cart
        $cart = $this->getOrCreateCart($tenant, $customerInfo);

        // Call Shopify Storefront API to add item
        $cartData = $this->shopifyService->addCartLine(
            $tenant,
            $cart->shopify_cart_id,
            $variantId,
            $quantity,
            $customerInfo['shopify_token'] ?? null
        );

        // Update cart
        $cart->update([
            'cart_data' => $cartData,
            'last_activity_at' => now(),
        ]);

        // Log event
        CartEvent::create([
            'tenant_id' => $tenant->id,
            'shopify_customer_id' => $customerInfo['shopify_customer_id'],
            'customer_email' => $customerInfo['customer_email'],
            'device_id' => $customerInfo['device_id'],
            'event_type' => 'add_to_cart',
            'product_id' => $productId,
            'variant_id' => $variantId,
            'quantity' => $quantity,
            'metadata' => $attributes,
        ]);

        return $cartData;
    }

    public function removeFromCart(
        Tenant $tenant,
        array $customerInfo,
        string $lineId
    ): array {
        $cart = $this->findCart($tenant, $customerInfo);

        if (!$cart) {
            throw new \Exception('Cart not found');
        }

        // Call Shopify to remove line
        $cartData = $this->shopifyService->removeCartLine(
            $tenant,
            $cart->shopify_cart_id,
            $lineId,
            $customerInfo['shopify_token'] ?? null
        );

        // Update cart
        $cart->update([
            'cart_data' => $cartData,
            'last_activity_at' => now(),
        ]);

        // Log event
        CartEvent::create([
            'tenant_id' => $tenant->id,
            'shopify_customer_id' => $customerInfo['shopify_customer_id'],
            'customer_email' => $customerInfo['customer_email'],
            'device_id' => $customerInfo['device_id'],
            'event_type' => 'remove_from_cart',
            'product_id' => $lineId,
            'variant_id' => null,
            'quantity' => 0,
        ]);

        return $cartData;
    }

    public function updateCartLine(
        Tenant $tenant,
        array $customerInfo,
        string $lineId,
        int $quantity
    ): array {
        $cart = $this->findCart($tenant, $customerInfo);

        if (!$cart) {
            throw new \Exception('Cart not found');
        }

        // Call Shopify to update line
        $cartData = $this->shopifyService->updateCartLine(
            $tenant,
            $cart->shopify_cart_id,
            $lineId,
            $quantity,
            $customerInfo['shopify_token'] ?? null
        );

        // Update cart
        $cart->update([
            'cart_data' => $cartData,
            'last_activity_at' => now(),
        ]);

        // Log event
        CartEvent::create([
            'tenant_id' => $tenant->id,
            'shopify_customer_id' => $customerInfo['shopify_customer_id'],
            'customer_email' => $customerInfo['customer_email'],
            'device_id' => $customerInfo['device_id'],
            'event_type' => 'update_quantity',
            'product_id' => $lineId,
            'variant_id' => null,
            'quantity' => $quantity,
        ]);

        return $cartData;
    }

    public function getCart(Tenant $tenant, array $customerInfo): ?array
    {
        $cart = $this->findCart($tenant, $customerInfo);

        if (!$cart) {
            return null;
        }

        // Optionally refresh from Shopify
        if ($cart->shopify_cart_id) {
            $cartData = $this->shopifyService->getCart(
                $tenant,
                $cart->shopify_cart_id,
                $customerInfo['shopify_token'] ?? null
            );

            $cart->update([
                'cart_data' => $cartData,
                'last_activity_at' => now(),
            ]);

            return $cartData;
        }

        return $cart->cart_data;
    }

    private function getOrCreateCart(Tenant $tenant, array $customerInfo): Cart
    {
        $cart = $this->findCart($tenant, $customerInfo);

        if ($cart) {
            return $cart;
        }

        // Create new cart in Shopify
        $cartData = $this->shopifyService->createCart($tenant, $customerInfo['shopify_token'] ?? null);

        // Save to database
        return Cart::create([
            'tenant_id' => $tenant->id,
            'shopify_customer_id' => $customerInfo['shopify_customer_id'],
            'device_id' => $customerInfo['device_id'],
            'shopify_cart_id' => $cartData['id'],
            'cart_data' => $cartData,
            'last_activity_at' => now(),
        ]);
    }

    private function findCart(Tenant $tenant, array $customerInfo): ?Cart
    {
        $query = Cart::where('tenant_id', $tenant->id);

        // Priority: shopify_customer_id > device_id
        if (!empty($customerInfo['shopify_customer_id'])) {
            $query->where('shopify_customer_id', $customerInfo['shopify_customer_id']);
        } else {
            $query->where('device_id', $customerInfo['device_id']);
        }

        return $query->latest('last_activity_at')->first();
    }

    public function extractCustomerInfo(array $headers): array
    {
        $shopifyToken = $headers['x-shopify-customer-token'] ?? null;
        $deviceId = $headers['x-device-id'] ?? null;

        $customerData = null;

        if ($shopifyToken) {
            // Get customer info from Shopify (requires tenant context)
            // Note: This should be called with tenant in the actual implementation
            $customerData = null; // Will be populated when needed
        }

        return [
            'shopify_customer_id' => $customerData['id'] ?? null,
            'customer_email' => $customerData['email'] ?? null,
            'device_id' => $deviceId,
            'shopify_token' => $shopifyToken,
        ];
    }
}

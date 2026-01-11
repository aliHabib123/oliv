<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Cart\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(
        private CartService $cartService
    ) {}

    public function add(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'product_id' => 'required|string',
                'variant_id' => 'required|string',
                'quantity' => 'integer|min:1',
                'attributes' => 'array',
            ]);

            $tenant = $request->attributes->get('tenant');

            $customerInfo = $this->cartService->extractCustomerInfo([
                'x-shopify-customer-token' => $request->header('X-Shopify-Customer-Token'),
                'x-device-id' => $request->header('X-Device-ID'),
            ]);

            $cart = $this->cartService->addToCart(
                tenant: $tenant,
                customerInfo: $customerInfo,
                productId: $validated['product_id'],
                variantId: $validated['variant_id'],
                quantity: $validated['quantity'] ?? 1,
                attributes: $validated['attributes'] ?? null
            );

            return response()->json([
                'ok' => true,
                'cart' => $cart,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function remove(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'line_id' => 'required|string',
            ]);

            $tenant = $request->attributes->get('tenant');

            $customerInfo = $this->cartService->extractCustomerInfo([
                'x-shopify-customer-token' => $request->header('X-Shopify-Customer-Token'),
                'x-device-id' => $request->header('X-Device-ID'),
            ]);

            $cart = $this->cartService->removeFromCart(
                tenant: $tenant,
                customerInfo: $customerInfo,
                lineId: $validated['line_id']
            );

            return response()->json([
                'ok' => true,
                'cart' => $cart,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function update(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'line_id' => 'required|string',
                'quantity' => 'required|integer|min:0',
            ]);

            $tenant = $request->attributes->get('tenant');

            $customerInfo = $this->cartService->extractCustomerInfo([
                'x-shopify-customer-token' => $request->header('X-Shopify-Customer-Token'),
                'x-device-id' => $request->header('X-Device-ID'),
            ]);

            $cart = $this->cartService->updateCartLine(
                tenant: $tenant,
                customerInfo: $customerInfo,
                lineId: $validated['line_id'],
                quantity: $validated['quantity']
            );

            return response()->json([
                'ok' => true,
                'cart' => $cart,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function get(Request $request): JsonResponse
    {
        try {
            $tenant = $request->attributes->get('tenant');

            $customerInfo = $this->cartService->extractCustomerInfo([
                'x-shopify-customer-token' => $request->header('X-Shopify-Customer-Token'),
                'x-device-id' => $request->header('X-Device-ID'),
            ]);

            $cart = $this->cartService->getCart(
                tenant: $tenant,
                customerInfo: $customerInfo
            );

            return response()->json([
                'ok' => true,
                'cart' => $cart,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}

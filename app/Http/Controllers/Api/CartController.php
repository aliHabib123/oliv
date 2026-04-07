<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Cart\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

class CartController extends Controller
{
    public function __construct(
        private CartService $cartService
    ) {}

    #[OA\Post(
        path: '/v1/cart/add',
        tags: ['Cart'],
        summary: 'Add item to cart',
        security: [['XAppKey' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['product_id', 'variant_id'],
            properties: [
                new OA\Property(property: 'product_id', type: 'string'),
                new OA\Property(property: 'variant_id', type: 'string'),
                new OA\Property(property: 'quantity', type: 'integer', minimum: 1, default: 1),
                new OA\Property(property: 'attributes', type: 'object', nullable: true)
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Item added to cart',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'ok', type: 'boolean'),
                new OA\Property(property: 'cart', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 400, description: 'Bad request')]
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

    #[OA\Post(
        path: '/v1/cart/remove',
        tags: ['Cart'],
        summary: 'Remove item from cart',
        security: [['XAppKey' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['line_id'],
            properties: [
                new OA\Property(property: 'line_id', type: 'string')
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Item removed from cart',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'ok', type: 'boolean'),
                new OA\Property(property: 'cart', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 400, description: 'Bad request')]
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

    #[OA\Post(
        path: '/v1/cart/update',
        tags: ['Cart'],
        summary: 'Update cart item quantity',
        security: [['XAppKey' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['line_id', 'quantity'],
            properties: [
                new OA\Property(property: 'line_id', type: 'string'),
                new OA\Property(property: 'quantity', type: 'integer', minimum: 0)
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Cart item updated',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'ok', type: 'boolean'),
                new OA\Property(property: 'cart', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 400, description: 'Bad request')]
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

    #[OA\Get(
        path: '/v1/cart',
        tags: ['Cart'],
        summary: 'Get current cart',
        security: [['XAppKey' => []]]
    )]
    #[OA\Response(
        response: 200,
        description: 'Current cart data',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'ok', type: 'boolean'),
                new OA\Property(property: 'cart', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 400, description: 'Bad request')]
    public function get(Request $request): JsonResponse
    {
        try {
            $tenant = $request->attributes->get('tenant');

            $customerInfo = $this->cartService->extractCustomerInfo([
                'x-shopify-customer-token' => $request->header('X-Shopify-Customer-Token'),
                'x-device-id' => $request->header('X-Device-ID'),
            ]);

            Log::info('GET /cart request', [
                'tenant_id' => $tenant->id,
                'customer_info' => $customerInfo,
            ]);

            $cart = $this->cartService->getCart(
                tenant: $tenant,
                customerInfo: $customerInfo
            );

            Log::info('GET /cart response', [
                'tenant_id' => $tenant->id,
                'cart_id' => $cart['id'] ?? null,
                'line_count' => count($cart['lines']['edges'] ?? []),
            ]);

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

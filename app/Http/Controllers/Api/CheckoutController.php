<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CheckoutEvent;
use App\Services\Cart\CartService;
use App\Services\Shopify\ShopifyStorefrontService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class CheckoutController extends Controller
{
    public function __construct(
        private CartService $cartService,
        private ShopifyStorefrontService $shopifyService
    ) {}

    #[OA\Post(
        path: '/v1/checkout/create',
        tags: ['Checkout'],
        summary: 'Create Shopify checkout',
        security: [['XAppKey' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['line_items'],
            properties: [
                new OA\Property(
                    property: 'line_items',
                    type: 'array',
                    items: new OA\Items(
                        required: ['variant_id', 'quantity'],
                        properties: [
                            new OA\Property(property: 'variant_id', type: 'string'),
                            new OA\Property(property: 'quantity', type: 'integer', minimum: 1)
                        ]
                    )
                )
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Checkout created successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'ok', type: 'boolean'),
                new OA\Property(property: 'checkout_id', type: 'string'),
                new OA\Property(property: 'checkout_url', type: 'string'),
                new OA\Property(property: 'is_authenticated', type: 'boolean')
            ]
        )
    )]
    #[OA\Response(response: 400, description: 'Bad request')]
    public function create(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'line_items' => 'required|array',
                'line_items.*.variant_id' => 'required|string',
                'line_items.*.quantity' => 'required|integer|min:1',
            ]);

            $tenant = $request->attributes->get('tenant');

            $customerInfo = $this->cartService->extractCustomerInfo([
                'x-shopify-customer-token' => $request->header('X-Shopify-Customer-Token'),
                'x-device-id' => $request->header('X-Device-ID'),
            ]);

            // Get local cart model (for database ID)
            $localCart = $this->cartService->findCartModel($tenant, $customerInfo);

            // Create checkout via Shopify
            $checkout = $this->shopifyService->createCheckout(
                tenant: $tenant,
                lineItems: $validated['line_items'],
                customerToken: $customerInfo['shopify_token']
            );

            // Log checkout creation
            CheckoutEvent::create([
                'tenant_id' => $tenant->id,
                'cart_id' => $localCart?->id,
                'shopify_customer_id' => $customerInfo['shopify_customer_id'],
                'customer_email' => $customerInfo['customer_email'] ?? $checkout['email'] ?? null,
                'device_id' => $customerInfo['device_id'],
                'checkout_id' => $checkout['id'],
                'checkout_url' => $checkout['webUrl'],
                'event_type' => 'checkout_created',
                'line_items' => $validated['line_items'],
                'subtotal_price' => $this->extractPrice($checkout['subtotalPriceV2'] ?? null),
                'total_price' => $this->extractPrice($checkout['totalPriceV2'] ?? null),
            ]);

            return response()->json([
                'ok' => true,
                'checkout_id' => $checkout['id'],
                'checkout_url' => $checkout['webUrl'],
                'is_authenticated' => !empty($customerInfo['shopify_token']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    private function extractPrice(?array $priceData): ?float
    {
        if (!$priceData || !isset($priceData['amount'])) {
            return null;
        }

        return (float) $priceData['amount'];
    }
}

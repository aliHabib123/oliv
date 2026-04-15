<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Shopify\ShopifyAdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class OrderController extends Controller
{
    public function __construct(
        private ShopifyAdminService $shopifyAdmin,
    ) {}

    #[OA\Get(
        path: '/v1/orders/{order_id}/status',
        tags: ['Orders'],
        summary: 'Get order status including fulfillment, financial, and tracking info',
        security: [['AppKey' => []]]
    )]
    #[OA\Parameter(
        name: 'order_id',
        in: 'path',
        required: true,
        description: 'Shopify order ID (gid://shopify/Order/...) or order name (#1234)',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Response(
        response: 200,
        description: 'Order status retrieved',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'ok', type: 'boolean'),
                new OA\Property(property: 'order_id', type: 'string'),
                new OA\Property(property: 'order_name', type: 'string'),
                new OA\Property(property: 'financial_status', type: 'string', enum: ['pending', 'authorized', 'paid', 'partially_refunded', 'refunded', 'voided']),
                new OA\Property(property: 'fulfillment_status', type: 'string', enum: ['unfulfilled', 'partially_fulfilled', 'fulfilled', 'scheduled']),
                new OA\Property(property: 'fulfillments', type: 'array', items: new OA\Items(type: 'object')),
            ]
        )
    )]
    #[OA\Response(response: 404, description: 'Order not found')]
    public function status(Request $request, string $orderId): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');
        if (! $tenant) {
            return response()->json(['ok' => false, 'error' => 'Tenant not found'], 404);
        }

        // If order_id starts with #, look it up by order name
        if (str_starts_with($orderId, '#')) {
            $mapping = ShopifyOrderMapping::where('tenant_id', $tenant->id)
                ->where('shopify_order_name', $orderId)
                ->first();

            if (! $mapping) {
                return response()->json(['ok' => false, 'error' => 'Order not found'], 404);
            }

            $orderId = $mapping->shopify_order_id;
        }

        // If it's not a GID, try to find it in our mappings
        if (! str_starts_with($orderId, 'gid://')) {
            $mapping = ShopifyOrderMapping::where('tenant_id', $tenant->id)
                ->where('shopify_order_id', 'like', "%{$orderId}%")
                ->first();

            if ($mapping) {
                $orderId = $mapping->shopify_order_id;
            } else {
                // Assume it's just the numeric ID
                $orderId = "gid://shopify/Order/{$orderId}";
            }
        }

        try {
            $status = $this->shopifyAdmin->getOrderStatus($tenant, $orderId);

            return response()->json([
                'ok' => true,
                ...$status,
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'ok' => false,
                'error' => $e->getMessage(),
            ], 404);
        }
    }
}

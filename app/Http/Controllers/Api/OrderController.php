<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Shopify\ShopifyAdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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
                new OA\Property(property: 'tracking_status', type: 'string', enum: ['order_placed', 'out_for_delivery', 'delivered']),
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

        // Convert to GID format if needed
        if (! str_starts_with($orderId, 'gid://')) {
            // Check if it's an order name (e.g., #1142 or 1142)
            // Order names are typically short numbers, GID numeric parts are longer
            $cleanId = ltrim($orderId, '#');

            // If it looks like an order name (short number), query Shopify
            if (strlen($cleanId) <= 6 && is_numeric($cleanId)) {
                $foundId = $this->shopifyAdmin->findOrderIdByName($tenant, $cleanId);
                if ($foundId) {
                    $orderId = $foundId;
                } else {
                    return response()->json(['ok' => false, 'error' => 'Order not found'], 404);
                }
            } else {
                // Assume it's a numeric order ID
                $orderId = "gid://shopify/Order/{$cleanId}";
            }
        }

        try {
            $status = $this->shopifyAdmin->getOrderStatus($tenant, $orderId);

            // Check for tracking status override (for testing)
            $overrideKey = 'tracking_status_override:' . $orderId;
            $override = Cache::get($overrideKey);
            if ($override) {
                $status['tracking_status'] = $override;
            }

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

    /**
     * Override tracking status for testing purposes.
     */
    public function overrideTrackingStatus(Request $request, string $orderId): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');
        if (! $tenant) {
            return response()->json(['ok' => false, 'error' => 'Tenant not found'], 404);
        }

        $validated = $request->validate([
            'tracking_status' => 'required|string|in:order_placed,out_for_delivery,delivered',
        ]);

        // Convert to GID format if needed
        if (! str_starts_with($orderId, 'gid://')) {
            $cleanId = ltrim($orderId, '#');
            if (strlen($cleanId) <= 6 && is_numeric($cleanId)) {
                $foundId = $this->shopifyAdmin->findOrderIdByName($tenant, $cleanId);
                if ($foundId) {
                    $orderId = $foundId;
                } else {
                    return response()->json(['ok' => false, 'error' => 'Order not found'], 404);
                }
            } else {
                $orderId = "gid://shopify/Order/{$cleanId}";
            }
        }

        $overrideKey = 'tracking_status_override:' . $orderId;
        Cache::put($overrideKey, $validated['tracking_status'], now()->addDays(7));

        return response()->json([
            'ok' => true,
            'order_id' => $orderId,
            'tracking_status' => $validated['tracking_status'],
            'message' => 'Tracking status override set (expires in 7 days)',
        ]);
    }
}

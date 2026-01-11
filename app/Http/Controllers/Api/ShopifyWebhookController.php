<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CheckoutEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ShopifyWebhookController extends Controller
{
    public function checkoutCreate(Request $request): JsonResponse
    {
        try {
            $tenant = $request->attributes->get('tenant');

            if (!$tenant) {
                return response()->json(['error' => 'Tenant not resolved'], 404);
            }

            $data = $request->all();

            Log::info('Shopify checkout/create webhook received', [
                'tenant_id' => $tenant->id,
                'checkout_id' => $data['id'] ?? null,
            ]);

            return response()->json(['ok' => true]);
        } catch (\Exception $e) {
            Log::error('Error processing checkout/create webhook', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function checkoutUpdate(Request $request): JsonResponse
    {
        try {
            $tenant = $request->attributes->get('tenant');

            if (!$tenant) {
                return response()->json(['error' => 'Tenant not resolved'], 404);
            }

            $data = $request->all();

            Log::info('Shopify checkout/update webhook received', [
                'tenant_id' => $tenant->id,
                'checkout_id' => $data['id'] ?? null,
                'completed_at' => $data['completed_at'] ?? null,
            ]);

            return response()->json(['ok' => true]);
        } catch (\Exception $e) {
            Log::error('Error processing checkout/update webhook', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function orderCreate(Request $request): JsonResponse
    {
        try {
            $tenant = $request->attributes->get('tenant');

            if (!$tenant) {
                return response()->json(['error' => 'Tenant not resolved'], 404);
            }

            $data = $request->all();
            $checkoutId = $data['checkout_id'] ?? null;

            Log::info('Shopify orders/create webhook received', [
                'tenant_id' => $tenant->id,
                'order_id' => $data['id'] ?? null,
                'checkout_id' => $checkoutId,
                'total_price' => $data['total_price'] ?? null,
            ]);

            // Update checkout event to mark as completed
            if ($checkoutId) {
                CheckoutEvent::where('tenant_id', $tenant->id)
                    ->where('checkout_id', 'like', "%{$checkoutId}%")
                    ->whereNull('completed_at')
                    ->update([
                        'completed_at' => now(),
                        'event_type' => 'order_completed',
                    ]);
            }

            return response()->json(['ok' => true]);
        } catch (\Exception $e) {
            Log::error('Error processing orders/create webhook', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}

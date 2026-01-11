<?php

use App\Http\Middleware\ResolveTenantFromAppKey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(ResolveTenantFromAppKey::class)->group(function () {
    Route::get('v1/ping', function (Request $request) {
        /** @var \App\Models\Tenant|null $tenant */
        $tenant = $request->attributes->get('tenant');

        return response()->json([
            'ok' => true,
            'timestamp' => now()->toISOString(),
            'tenant' => $tenant ? [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'status' => $tenant->status,
            ] : null,
        ]);
    });

    Route::get('v1/home-layout', function (Request $request) {
        /** @var \App\Models\Tenant|null $tenant */
        $tenant = $request->attributes->get('tenant');

        if (! $tenant) {
            return response()->json([
                'ok' => false,
                'error' => 'Tenant not resolved.',
            ], 404);
        }

        return response()->json([
            'ok' => true,
            'layout' => new \App\Http\Resources\HomeLayoutResource($tenant->home_layout ?? []),
        ]);
    });

    Route::get('v1/introduction', function (Request $request) {
        /** @var \App\Models\Tenant|null $tenant */
        $tenant = $request->attributes->get('tenant');

        if (! $tenant) {
            return response()->json([
                'ok' => false,
                'error' => 'Tenant not resolved.',
            ], 404);
        }

        return response()->json([
            'ok' => true,
            'introduction' => new \App\Http\Resources\IntroductionResource($tenant->intro_config ?? []),
        ]);
    });

    Route::get('v1/app-config', function (Request $request) {
        /** @var \App\Models\Tenant|null $tenant */
        $tenant = $request->attributes->get('tenant');

        if (! $tenant) {
            return response()->json([
                'ok' => false,
                'error' => 'Tenant not resolved.',
            ], 404);
        }

        $config = \App\Models\AppConfiguration::where('tenant_id', $tenant->id)->first();

        return response()->json([
            'ok' => true,
            'config' => new \App\Http\Resources\AppConfigResource($config?->config ?? []),
        ]);
    });

    // Product helper endpoints
    Route::get('v1/products/{productId}/default-variant', [App\Http\Controllers\Api\ProductController::class, 'getDefaultVariant']);

    // Cart endpoints
    Route::post('v1/cart/add', [App\Http\Controllers\Api\CartController::class, 'add']);
    Route::post('v1/cart/remove', [App\Http\Controllers\Api\CartController::class, 'remove']);
    Route::post('v1/cart/update', [App\Http\Controllers\Api\CartController::class, 'update']);
    Route::get('v1/cart', [App\Http\Controllers\Api\CartController::class, 'get']);

    // Checkout endpoints
    Route::post('v1/checkout/create', [App\Http\Controllers\Api\CheckoutController::class, 'create']);

    // Shopify Webhooks
    Route::post('webhooks/shopify/checkouts/create', [App\Http\Controllers\Api\ShopifyWebhookController::class, 'checkoutCreate']);
    Route::post('webhooks/shopify/checkouts/update', [App\Http\Controllers\Api\ShopifyWebhookController::class, 'checkoutUpdate']);
    Route::post('webhooks/shopify/orders/create', [App\Http\Controllers\Api\ShopifyWebhookController::class, 'orderCreate']);
});

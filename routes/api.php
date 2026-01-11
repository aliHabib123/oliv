<?php

use App\Http\Controllers\Api\ContentController;
use App\Http\Middleware\ResolveTenantFromAppKey;
use Illuminate\Support\Facades\Route;

Route::middleware(ResolveTenantFromAppKey::class)->group(function () {
    // Content endpoints
    Route::get('v1/ping', [ContentController::class, 'ping']);
    Route::get('v1/home-layout', [ContentController::class, 'homeLayout']);
    Route::get('v1/introduction', [ContentController::class, 'introduction']);
    Route::get('v1/app-config', [ContentController::class, 'appConfig']);

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

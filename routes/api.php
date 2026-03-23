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
    Route::get('v1/payment-settings', [ContentController::class, 'paymentSettings']);

    // Product helper endpoints
    Route::get('v1/products/{productId}/default-variant', [App\Http\Controllers\Api\ProductController::class, 'getDefaultVariant']);

    // Device token endpoints
    Route::post('v1/device-tokens/register', [App\Http\Controllers\Api\DeviceTokenController::class, 'register']);
    Route::post('v1/device-tokens/unregister', [App\Http\Controllers\Api\DeviceTokenController::class, 'unregister']);

    // Addon endpoints
    Route::get('v1/addons', [App\Http\Controllers\Api\AddonController::class, 'index']);

    // Cart endpoints
    Route::post('v1/cart/add', [App\Http\Controllers\Api\CartController::class, 'add']);
    Route::post('v1/cart/remove', [App\Http\Controllers\Api\CartController::class, 'remove']);
    Route::post('v1/cart/update', [App\Http\Controllers\Api\CartController::class, 'update']);
    Route::get('v1/cart', [App\Http\Controllers\Api\CartController::class, 'get']);

    // Checkout endpoints
    Route::post('v1/checkout/create', [App\Http\Controllers\Api\CheckoutController::class, 'create']);
    Route::post('v1/checkout/session', [App\Http\Controllers\Api\CheckoutSessionController::class, 'create']);
    Route::post('v1/checkout/confirm', [App\Http\Controllers\Api\CheckoutSessionController::class, 'confirm']);

    // Payment endpoints
    Route::post('v1/pay', [App\Http\Controllers\Api\PaymentController::class, 'pay']);
    Route::post('v1/payments/tabby/initialize', [App\Http\Controllers\Api\TabbyController::class, 'initialize']);
    Route::post('v1/payments/tamara/initialize', [App\Http\Controllers\Api\TamaraController::class, 'initialize']);

    // Shopify Webhooks
    Route::post('webhooks/shopify/checkouts/create', [App\Http\Controllers\Api\ShopifyWebhookController::class, 'checkoutCreate']);
    Route::post('webhooks/shopify/checkouts/update', [App\Http\Controllers\Api\ShopifyWebhookController::class, 'checkoutUpdate']);
    Route::post('webhooks/shopify/orders/create', [App\Http\Controllers\Api\ShopifyWebhookController::class, 'orderCreate']);
});

// PSP Webhook endpoints (no tenant middleware — resolved from payload)
Route::post('webhooks/tabby', [App\Http\Controllers\Api\TabbyController::class, 'webhook']);
Route::post('webhooks/tamara', [App\Http\Controllers\Api\TamaraController::class, 'webhook']);

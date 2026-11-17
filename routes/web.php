<?php

use App\Http\Controllers\Onboarding\BrandingController;
use App\Http\Controllers\Onboarding\CategoriesController;
use App\Http\Controllers\Onboarding\FinishController;
use App\Http\Controllers\Onboarding\ShopifyOnboardingController;
use App\Http\Controllers\Onboarding\StoreInfoController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\TenantAddonController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect('/admin');
    }

    return redirect('/admin/login');
});

// Public subscription routes
Route::get('/subscriptions', [SubscriptionController::class, 'index'])->name('subscriptions.index');
Route::post('/subscriptions/set-country', [SubscriptionController::class, 'setCountry'])->name('subscriptions.set-country');
Route::post('/subscriptions/subscribe', [SubscriptionController::class, 'subscribe'])->name('subscriptions.subscribe');
Route::get('/subscriptions/payment/{tenant}/{subscription}', [SubscriptionController::class, 'payment'])->name('subscriptions.payment');
Route::get('/subscriptions/success', [SubscriptionController::class, 'success'])->name('subscriptions.success');
Route::post('/webhooks/stripe', [SubscriptionController::class, 'webhook'])->name('webhooks.stripe');

// Onboarding wizard — Step 1: connect Shopify (post-payment, no auth yet).
// The show route is signed to prevent tenant enumeration; the OAuth callback is
// verified via state + HMAC instead (Shopify cannot carry our signature).
Route::prefix('onboarding')->name('onboarding.')->group(function () {
    Route::get('/{tenant}/shopify', [ShopifyOnboardingController::class, 'show'])
        ->name('shopify.show')
        ->middleware('signed');
    Route::post('/{tenant}/shopify/connect', [ShopifyOnboardingController::class, 'connect'])
        ->name('shopify.connect');
    Route::get('/shopify/callback', [ShopifyOnboardingController::class, 'callback'])
        ->name('shopify.callback');

    // Step 2: branding (app name, logo, colors) — pre-filled from Shopify brand assets.
    Route::get('/{tenant}/branding', [BrandingController::class, 'show'])
        ->name('branding.show')
        ->middleware('signed');
    Route::post('/{tenant}/branding', [BrandingController::class, 'save'])
        ->name('branding.save');

    // Step 3: categories — pick collections; builds the Shopify `flutter-menu`.
    Route::get('/{tenant}/categories', [CategoriesController::class, 'show'])
        ->name('categories.show')
        ->middleware('signed');
    Route::post('/{tenant}/categories', [CategoriesController::class, 'save'])
        ->name('categories.save');

    // Step 4: store info — contact, social, policy URLs (all optional).
    Route::get('/{tenant}/store-info', [StoreInfoController::class, 'show'])
        ->name('store-info.show')
        ->middleware('signed');
    Route::post('/{tenant}/store-info', [StoreInfoController::class, 'save'])
        ->name('store-info.save');

    // Step 5: finish — completion screen.
    Route::get('/{tenant}/finish', [FinishController::class, 'show'])
        ->name('finish.show')
        ->middleware('signed');
});

// Tenant addon routes (requires authentication)
Route::middleware('auth')->group(function () {
    Route::get('/tenant-addons', [TenantAddonController::class, 'index'])->name('tenant-addons.index');
    Route::post('/tenant-addons/{addon}/subscribe', [TenantAddonController::class, 'subscribe'])->name('tenant-addons.subscribe');
    Route::delete('/tenant-addons/{addon}/unsubscribe', [TenantAddonController::class, 'unsubscribe'])->name('tenant-addons.unsubscribe');
});

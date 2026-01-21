<?php

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
Route::post('/subscriptions/subscribe', [SubscriptionController::class, 'subscribe'])->name('subscriptions.subscribe');

// Tenant addon routes (requires authentication)
Route::middleware('auth')->group(function () {
    Route::get('/tenant-addons', [TenantAddonController::class, 'index'])->name('tenant-addons.index');
    Route::post('/tenant-addons/{addon}/subscribe', [TenantAddonController::class, 'subscribe'])->name('tenant-addons.subscribe');
    Route::delete('/tenant-addons/{addon}/unsubscribe', [TenantAddonController::class, 'unsubscribe'])->name('tenant-addons.unsubscribe');
});

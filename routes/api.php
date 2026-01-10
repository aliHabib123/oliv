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
            'layout' => $tenant->home_layout ?? [],
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
            'introduction' => $tenant->intro_config ?? [],
        ]);
    });
});

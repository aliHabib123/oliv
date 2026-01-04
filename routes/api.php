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
});

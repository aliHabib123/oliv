<?php

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('v1/ping', function (Request $request) {
    $appKey = $request->header('X-App-Key');

    $tenant = $appKey
        ? Tenant::where('app_key', $appKey)->first()
        : null;

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

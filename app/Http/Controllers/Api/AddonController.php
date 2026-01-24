<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TenantAddon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AddonController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');

        if (!$tenant) {
            return response()->json([
                'ok' => false,
                'error' => 'Tenant not found',
            ], 404);
        }

        $addons = TenantAddon::where('tenant_id', $tenant->id)
            ->where('status', 'active')
            ->with('addon:id,name,key,description,price')
            ->get()
            ->map(function ($tenantAddon) {
                return [
                    'id' => $tenantAddon->addon->id,
                    'name' => $tenantAddon->addon->name,
                    'key' => $tenantAddon->addon->key,
                    'description' => $tenantAddon->addon->description,
                    'price' => $tenantAddon->addon->price,
                    'subscribed_at' => $tenantAddon->subscribed_at?->toIso8601String(),
                    'expires_at' => $tenantAddon->expires_at?->toIso8601String(),
                ];
            });

        return response()->json([
            'ok' => true,
            'addons' => $addons,
        ]);
    }
}

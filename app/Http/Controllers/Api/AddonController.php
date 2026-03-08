<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TenantAddon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class AddonController extends Controller
{
    #[OA\Get(
        path: '/v1/addons',
        tags: ['Addons'],
        summary: 'Get active tenant addons',
        security: [['XAppKey' => []]]
    )]
    #[OA\Response(
        response: 200,
        description: 'List of active addons',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'ok', type: 'boolean'),
                new OA\Property(
                    property: 'addons',
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'id', type: 'integer'),
                            new OA\Property(property: 'name', type: 'string'),
                            new OA\Property(property: 'key', type: 'string'),
                            new OA\Property(property: 'description', type: 'string', nullable: true),
                            new OA\Property(property: 'price', type: 'number'),
                            new OA\Property(property: 'subscribed_at', type: 'string', nullable: true),
                            new OA\Property(property: 'expires_at', type: 'string', nullable: true)
                        ]
                    )
                )
            ]
        )
    )]
    #[OA\Response(response: 404, description: 'Tenant not found')]
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

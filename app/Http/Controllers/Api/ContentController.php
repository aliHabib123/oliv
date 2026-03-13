<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AppConfigResource;
use App\Http\Resources\HomeLayoutResource;
use App\Http\Resources\IntroductionResource;
use App\Models\AppConfiguration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\Shopify\ShopifyStorefrontService;
use Illuminate\Support\Facades\Cache;
use OpenApi\Attributes as OA;

class ContentController extends Controller
{
    #[OA\Get(
        path: '/v1/ping',
        tags: ['Content'],
        summary: 'Ping endpoint',
        security: [['XAppKey' => []]]
    )]
    #[OA\Response(
        response: 200,
        description: 'Pong response',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'ok', type: 'boolean'),
                new OA\Property(property: 'timestamp', type: 'string'),
                new OA\Property(property: 'tenant', type: 'object', nullable: true)
            ]
        )
    )]
    public function ping(Request $request): JsonResponse
    {
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
    }

    #[OA\Get(
        path: '/v1/home-layout',
        tags: ['Content'],
        summary: 'Get home layout for a category',
        security: [['XAppKey' => []]]
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'query',
        required: false,
        description: 'Category handle',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Response(
        response: 200,
        description: 'Layout data',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'ok', type: 'boolean'),
                new OA\Property(property: 'layout', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 404, description: 'Tenant not resolved')]
    public function homeLayout(Request $request): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');

        if (!$tenant) {
            return response()->json([
                'ok' => false,
                'error' => 'Tenant not resolved.',
            ], 404);
        }

        $categoryHandle = $request->query('id');

        // Cache key based on tenant and category
        $cacheKey = "home_layout:{$tenant->id}:" . ($categoryHandle ?? 'default');

        // Try to get from cache (5 minutes TTL)
        $layoutData = Cache::remember($cacheKey, 300, function () use ($tenant, $categoryHandle) {
            // Try to get layout from home_layouts table
            if ($categoryHandle) {
                // Look for category-specific layout
                $homeLayout = \App\Models\HomeLayout::where('tenant_id', $tenant->id)
                    ->where('category_handle', $categoryHandle)
                    ->where('status', 'active')
                    ->first();

                if ($homeLayout) {
                    return $homeLayout->layout_data;
                }
            }

            // Fallback to default layout if no category-specific layout found
            $defaultLayout = \App\Models\HomeLayout::where('tenant_id', $tenant->id)
                ->where('is_default', true)
                ->where('status', 'active')
                ->first();

            if ($defaultLayout) {
                return $defaultLayout->layout_data;
            }

            // Final fallback to tenant.home_layout for backward compatibility
            return $tenant->home_layout ?? [];
        });

        return response()->json([
            'ok' => true,
            'layout' => new HomeLayoutResource($layoutData),
        ]);
    }

    #[OA\Get(
        path: '/v1/introduction',
        tags: ['Content'],
        summary: 'Get introduction configuration',
        security: [['XAppKey' => []]]
    )]
    #[OA\Response(
        response: 200,
        description: 'Introduction config',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'ok', type: 'boolean'),
                new OA\Property(property: 'introduction', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 404, description: 'Tenant not resolved')]
    public function introduction(Request $request): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');

        if (!$tenant) {
            return response()->json([
                'ok' => false,
                'error' => 'Tenant not resolved.',
            ], 404);
        }

        return response()->json([
            'ok' => true,
            'introduction' => new IntroductionResource($tenant->intro_config ?? []),
        ]);
    }

    #[OA\Get(
        path: '/v1/app-config',
        tags: ['Content'],
        summary: 'Get app configuration',
        security: [['XAppKey' => []]]
    )]
    #[OA\Response(
        response: 200,
        description: 'App config',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'ok', type: 'boolean'),
                new OA\Property(property: 'config', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 404, description: 'Tenant not resolved')]
    public function appConfig(Request $request): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');

        if (!$tenant) {
            return response()->json([
                'ok' => false,
                'error' => 'Tenant not resolved.',
            ], 404);
        }

        $config = AppConfiguration::where('tenant_id', $tenant->id)->first();

        return response()->json([
            'ok' => true,
            'config' => new AppConfigResource($config?->config ?? []),
        ]);
    }

    #[OA\Get(
        path: '/v1/payment-settings',
        tags: ['Payment'],
        summary: 'Get payment settings for tenant',
        security: [['XAppKey' => []]]
    )]
    #[OA\Response(
        response: 200,
        description: 'Payment settings',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'ok', type: 'boolean'),
                new OA\Property(property: 'payment_settings', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 404, description: 'Tenant not resolved')]
    public function paymentSettings(Request $request): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');

        if (!$tenant) {
            return response()->json([
                'ok' => false,
                'error' => 'Tenant not resolved.',
            ], 404);
        }

        $cacheKey = "payment_settings:{$tenant->id}";

        $paymentSettings = Cache::remember($cacheKey, 3600, function () use ($tenant) {
            $shopifyService = new ShopifyStorefrontService();
            return $shopifyService->getPaymentSettings($tenant);
        });

        return response()->json([
            'ok' => true,
            'payment_settings' => $paymentSettings,
        ]);
    }
}

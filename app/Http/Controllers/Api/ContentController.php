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

class ContentController extends Controller
{
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

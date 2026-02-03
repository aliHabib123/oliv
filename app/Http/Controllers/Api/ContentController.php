<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AppConfigResource;
use App\Http\Resources\HomeLayoutResource;
use App\Http\Resources\IntroductionResource;
use App\Models\AppConfiguration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        // Log request details
        \Log::info('Home Layout API Request', [
            'query_strings' => $request->query->all(),
            'headers' => [
                'user-agent' => $request->header('User-Agent'),
                'accept' => $request->header('Accept'),
                'content-type' => $request->header('Content-Type'),
                'x-app-version' => $request->header('X-App-Version'),
                'x-platform' => $request->header('X-Platform'),
                'x-device-id' => $request->header('X-Device-Id'),
                'authorization' => $request->header('Authorization') ? 'Present' : 'Absent',
            ],
            'all_headers' => $request->headers->all(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'tenant_id' => $tenant?->id,
            'tenant_name' => $tenant?->name,
        ]);

        if (!$tenant) {
            return response()->json([
                'ok' => false,
                'error' => 'Tenant not resolved.',
            ], 404);
        }

        $categoryHandle = $request->query('category');
        $layoutData = null;

        // Try to get layout from home_layouts table
        if ($categoryHandle) {
            // Look for category-specific layout
            $homeLayout = \App\Models\HomeLayout::where('tenant_id', $tenant->id)
                ->where('category_handle', $categoryHandle)
                ->where('status', 'active')
                ->first();

            if ($homeLayout) {
                $layoutData = $homeLayout->layout_data;
            }
        }

        // Fallback to default layout if no category-specific layout found
        if (!$layoutData) {
            $defaultLayout = \App\Models\HomeLayout::where('tenant_id', $tenant->id)
                ->where('is_default', true)
                ->where('status', 'active')
                ->first();

            if ($defaultLayout) {
                $layoutData = $defaultLayout->layout_data;
            }
        }

        // Final fallback to tenant.home_layout for backward compatibility
        if (!$layoutData) {
            $layoutData = $tenant->home_layout ?? [];
        }

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
}

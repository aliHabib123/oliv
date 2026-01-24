<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DeviceTokenController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'push_token' => 'required|string',
            'user_id' => 'nullable|integer|exists:users,id',
            'provider' => 'nullable|string|in:onesignal,firebase',
            'device_type' => 'nullable|string',
            'platform' => 'nullable|string|in:ios,android',
            'app_version' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $tenant = $request->attributes->get('tenant');

            if (!$tenant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tenant not found',
                ], 400);
            }

            $deviceToken = DeviceToken::registerToken(
                tenantId: $tenant->id,
                pushToken: $request->push_token,
                userId: $request->user_id,
                provider: $request->provider ?? 'onesignal',
                deviceType: $request->device_type,
                platform: $request->platform,
                appVersion: $request->app_version,
                metadata: $request->metadata
            );

            return response()->json([
                'success' => true,
                'message' => 'Device token registered successfully',
                'data' => [
                    'id' => $deviceToken->id,
                    'push_token' => $deviceToken->push_token,
                    'provider' => $deviceToken->provider,
                    'registered_at' => $deviceToken->created_at,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to register device token',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function unregister(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'push_token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $tenant = $request->attributes->get('tenant');

            if (!$tenant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tenant not found',
                ], 400);
            }

            $deleted = DeviceToken::where('tenant_id', $tenant->id)
                ->where('push_token', $request->push_token)
                ->delete();

            return response()->json([
                'success' => true,
                'message' => $deleted ? 'Device token unregistered successfully' : 'Device token not found',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to unregister device token',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

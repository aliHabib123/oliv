<?php

namespace App\Services\OneSignal;

use App\Models\DeviceToken;
use App\Models\Tenant;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OneSignalService
{
    protected string $apiUrl = 'https://onesignal.com/api/v1';
    protected ?string $appId = null;
    protected ?string $apiKey = null;

    public function __construct()
    {
        $this->appId = config('services.onesignal.app_id');
        $this->apiKey = config('services.onesignal.api_key');
    }

    public function sendNotification(
        string $title,
        string $message,
        ?array $data = null,
        ?array $playerIds = null,
        ?int $userId = null,
        bool $sendToAll = false
    ): array {
        if (!$this->appId || !$this->apiKey) {
            throw new \Exception('OneSignal credentials not configured');
        }

        $payload = [
            'app_id' => $this->appId,
            'headings' => ['en' => $title],
            'contents' => ['en' => $message],
        ];

        if ($data) {
            $payload['data'] = $data;
        }

        if ($sendToAll) {
            $payload['included_segments'] = ['All'];
        } elseif ($playerIds) {
            $payload['include_player_ids'] = $playerIds;
        } elseif ($userId) {
            $payload['filters'] = [
                ['field' => 'tag', 'key' => 'user_id', 'relation' => '=', 'value' => (string)$userId]
            ];
        } else {
            throw new \Exception('No target specified for notification');
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl . '/notifications', $payload);

            if (!$response->successful()) {
                Log::error('OneSignal API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [
                    'success' => false,
                    'error' => $response->json('errors')[0] ?? 'Failed to send notification',
                ];
            }

            return [
                'success' => true,
                'id' => $response->json('id'),
                'recipients' => $response->json('recipients'),
            ];
        } catch (\Exception $e) {
            Log::error('OneSignal service error', [
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function sendToTenantUsers(Tenant $tenant, string $title, string $message, ?array $data = null, ?int $specificUserId = null): array
    {
        $query = DeviceToken::where('tenant_id', $tenant->id)
            ->where('provider', 'onesignal');

        if ($specificUserId) {
            $query->where('user_id', $specificUserId);
        }

        $tokens = $query->pluck('push_token')->toArray();

        if (empty($tokens)) {
            return [
                'success' => false,
                'error' => 'No registered devices found',
            ];
        }

        return $this->sendNotification(
            title: $title,
            message: $message,
            data: $data,
            playerIds: $tokens
        );
    }

    public function sendToAllTenantDevices(Tenant $tenant, string $title, string $message, ?array $data = null): array
    {
        return $this->sendToTenantUsers($tenant, $title, $message, $data);
    }

    public function sendToSpecificUser(Tenant $tenant, int $userId, string $title, string $message, ?array $data = null): array
    {
        return $this->sendToTenantUsers($tenant, $title, $message, $data, $userId);
    }
}

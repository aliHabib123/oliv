<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceToken extends Model
{
    protected $fillable = [
        'tenant_id',
        'user_id',
        'push_token',
        'provider',
        'device_type',
        'platform',
        'app_version',
        'metadata',
        'last_active_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'last_active_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function registerToken(
        int $tenantId,
        string $pushToken,
        ?int $userId = null,
        ?string $provider = 'onesignal',
        ?string $deviceType = null,
        ?string $platform = null,
        ?string $appVersion = null,
        ?array $metadata = null
    ): self {
        return static::updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'push_token' => $pushToken,
            ],
            [
                'user_id' => $userId,
                'provider' => $provider,
                'device_type' => $deviceType,
                'platform' => $platform,
                'app_version' => $appVersion,
                'metadata' => $metadata,
                'last_active_at' => now(),
            ]
        );
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentGateway extends Model
{
    protected $fillable = [
        'tenant_id',
        'gateway_type',
        'is_enabled',
        'config',
        'display_name',
        'sort_order',
        'is_test_mode',
        'supports_apple_pay',
        'supports_google_pay',
        'apple_pay_enabled',
        'google_pay_enabled',
        'wallet_config',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'is_test_mode' => 'boolean',
        'config' => 'encrypted:array',
        'sort_order' => 'integer',
        'supports_apple_pay' => 'boolean',
        'supports_google_pay' => 'boolean',
        'apple_pay_enabled' => 'boolean',
        'google_pay_enabled' => 'boolean',
        'wallet_config' => 'encrypted:array',
    ];

    protected $attributes = [
        'is_enabled' => false,
        'is_test_mode' => true,
        'sort_order' => 0,
        'supports_apple_pay' => false,
        'supports_google_pay' => false,
        'apple_pay_enabled' => false,
        'google_pay_enabled' => false,
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get only the fields that are safe to expose in public API.
     * Filters out sensitive credentials based on gateway type definition.
     */
    public function getPublicConfig(): array
    {
        $gatewayType = \App\Enums\PaymentGatewayType::tryFrom($this->gateway_type);

        if (!$gatewayType) {
            return [];
        }

        $config = $this->config ?? [];
        $publicFields = $gatewayType->getPublicFields();

        return array_intersect_key($config, array_flip($publicFields));
    }

    /**
     * Get configuration for server-side payment processing.
     * Returns all fields including sensitive credentials.
     */
    public function getServerConfig(): array
    {
        return $this->config ?? [];
    }

    /**
     * Validate that all required fields are present for this gateway type.
     */
    public function hasValidConfig(): bool
    {
        $gatewayType = \App\Enums\PaymentGatewayType::tryFrom($this->gateway_type);

        if (!$gatewayType) {
            return false;
        }

        $config = $this->config ?? [];
        $requiredFields = $gatewayType->getRequiredFields();

        foreach ($requiredFields as $field) {
            if (empty($config[$field])) {
                return false;
            }
        }

        return true;
    }
}

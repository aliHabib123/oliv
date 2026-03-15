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
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'is_test_mode' => 'boolean',
        'config' => 'encrypted:array',
        'sort_order' => 'integer',
    ];

    protected $attributes = [
        'is_enabled' => false,
        'is_test_mode' => true,
        'sort_order' => 0,
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

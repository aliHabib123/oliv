<?php

namespace App\Enums;

enum PaymentGatewayType: string
{
    case TABBY = 'tabby';
    case TAMARA = 'tamara';
    case STRIPE = 'stripe';
    case PAYPAL = 'paypal';
    case TELR = 'telr';

    /**
     * Get human-readable label for this gateway.
     */
    public function getLabel(): string
    {
        return match($this) {
            self::TABBY => 'Tabby',
            self::TAMARA => 'Tamara',
            self::STRIPE => 'Stripe',
            self::PAYPAL => 'PayPal',
            self::TELR => 'Telr',
        };
    }

    /**
     * Get all field definitions for this gateway type.
     * Returns array of field configurations with metadata.
     */
    public function getFieldDefinitions(): array
    {
        return match($this) {
            self::TABBY => [
                'public_key' => [
                    'label' => 'Public Key',
                    'type' => 'text',
                    'required' => true,
                    'public' => true, // exposed in API
                    'hint' => 'Your Tabby public API key (starts with pk_)',
                ],
                'secret_key' => [
                    'label' => 'Secret Key',
                    'type' => 'password',
                    'required' => true,
                    'public' => false, // server-side only
                    'hint' => 'Your Tabby secret API key (starts with sk_)',
                ],
                'merchant_code' => [
                    'label' => 'Merchant Code',
                    'type' => 'text',
                    'required' => false,
                    'public' => true,
                    'hint' => 'Optional merchant identifier',
                ],
            ],

            self::TAMARA => [
                'api_token' => [
                    'label' => 'API Token',
                    'type' => 'password',
                    'required' => true,
                    'public' => false,
                    'hint' => 'Your Tamara API authentication token',
                ],
                'merchant_url' => [
                    'label' => 'Merchant URL',
                    'type' => 'text',
                    'required' => false,
                    'public' => true,
                    'hint' => 'Optional merchant website URL',
                ],
                'notification_webhook_id' => [
                    'label' => 'Notification Webhook ID',
                    'type' => 'text',
                    'required' => false,
                    'public' => false,
                    'hint' => 'Webhook ID for payment notifications',
                ],
            ],

            self::STRIPE => [
                'publishable_key' => [
                    'label' => 'Publishable Key',
                    'type' => 'text',
                    'required' => true,
                    'public' => true,
                    'hint' => 'Your Stripe publishable key (starts with pk_)',
                ],
                'secret_key' => [
                    'label' => 'Secret Key',
                    'type' => 'password',
                    'required' => true,
                    'public' => false,
                    'hint' => 'Your Stripe secret key (starts with sk_)',
                ],
                'webhook_secret' => [
                    'label' => 'Webhook Secret',
                    'type' => 'password',
                    'required' => false,
                    'public' => false,
                    'hint' => 'Webhook signing secret for event verification',
                ],
            ],

            self::PAYPAL => [
                'client_id' => [
                    'label' => 'Client ID',
                    'type' => 'text',
                    'required' => true,
                    'public' => true,
                    'hint' => 'Your PayPal REST API client ID',
                ],
                'client_secret' => [
                    'label' => 'Client Secret',
                    'type' => 'password',
                    'required' => true,
                    'public' => false,
                    'hint' => 'Your PayPal REST API client secret',
                ],
            ],

            self::TELR => [
                'store_id' => [
                    'label' => 'Store ID',
                    'type' => 'text',
                    'required' => true,
                    'public' => true,
                    'hint' => 'Your Telr store identifier',
                ],
                'auth_key' => [
                    'label' => 'Authentication Key',
                    'type' => 'password',
                    'required' => true,
                    'public' => false,
                    'hint' => 'Your Telr authentication key',
                ],
            ],
        };
    }

    /**
     * Get list of required field names.
     */
    public function getRequiredFields(): array
    {
        $definitions = $this->getFieldDefinitions();
        return array_keys(array_filter($definitions, fn($def) => $def['required']));
    }

    /**
     * Get list of field names that are safe to expose in public API.
     */
    public function getPublicFields(): array
    {
        $definitions = $this->getFieldDefinitions();
        return array_keys(array_filter($definitions, fn($def) => $def['public']));
    }

    /**
     * Get list of field names that should only be used server-side.
     */
    public function getPrivateFields(): array
    {
        $definitions = $this->getFieldDefinitions();
        return array_keys(array_filter($definitions, fn($def) => !$def['public']));
    }

    /**
     * Get base API URL for this gateway.
     */
    public function getApiUrl(bool $isTestMode): string
    {
        return match($this) {
            self::TABBY => $isTestMode 
                ? 'https://api.tabby.dev' 
                : 'https://api.tabby.ai',
            self::TAMARA => $isTestMode 
                ? 'https://api-sandbox.tamara.co' 
                : 'https://api.tamara.co',
            self::STRIPE => $isTestMode 
                ? 'https://api.stripe.com' 
                : 'https://api.stripe.com',
            self::PAYPAL => $isTestMode 
                ? 'https://api-m.sandbox.paypal.com' 
                : 'https://api-m.paypal.com',
            self::TELR => $isTestMode 
                ? 'https://secure.telr.com' 
                : 'https://secure.telr.com',
        };
    }

    /**
     * Get all available gateway types as options array.
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn(self $type) => [$type->value => $type->getLabel()])
            ->all();
    }
}

<?php

namespace App\Enums;

enum PaymentGatewayType: string
{
    case TABBY = 'tabby';
    case TAMARA = 'tamara';
    case STRIPE = 'stripe';
    case PAYPAL = 'paypal';
    case TELR = 'telr';
    case AMAZON_PAYMENT_SERVICES = 'amazon_payment_services';
    case TAP = 'tap';

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
            self::AMAZON_PAYMENT_SERVICES => 'Amazon Payment Services',
            self::TAP => 'Tap Payments',
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
                    'public' => true,
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

            self::AMAZON_PAYMENT_SERVICES => [
                'merchant_identifier' => [
                    'label' => 'Merchant Identifier',
                    'type' => 'text',
                    'required' => true,
                    'public' => true,
                    'hint' => 'Your APS merchant identifier',
                ],
                'access_code' => [
                    'label' => 'Access Code',
                    'type' => 'password',
                    'required' => true,
                    'public' => true,
                    'hint' => 'Your APS access code',
                ],
                'sha_request_phrase' => [
                    'label' => 'SHA Request Phrase',
                    'type' => 'password',
                    'required' => true,
                    'public' => false,
                    'hint' => 'SHA request phrase for signature generation',
                ],
                'sha_response_phrase' => [
                    'label' => 'SHA Response Phrase',
                    'type' => 'password',
                    'required' => true,
                    'public' => false,
                    'hint' => 'SHA response phrase for signature verification',
                ],
                'sha_type' => [
                    'label' => 'SHA Type',
                    'type' => 'text',
                    'required' => false,
                    'public' => false,
                    'hint' => 'SHA type: SHA-256 or SHA-512 (default: SHA-256)',
                ],
            ],

            self::TAP => [
                'public_key' => [
                    'label' => 'Public Key',
                    'type' => 'text',
                    'required' => true,
                    'public' => true,
                    'hint' => 'Your Tap publishable API key (starts with pk_)',
                ],
                'secret_key' => [
                    'label' => 'Secret Key',
                    'type' => 'password',
                    'required' => true,
                    'public' => false,
                    'hint' => 'Your Tap secret API key (starts with sk_)',
                ],
                'merchant_id' => [
                    'label' => 'Merchant ID',
                    'type' => 'text',
                    'required' => false,
                    'public' => true,
                    'hint' => 'Optional merchant identifier',
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
            self::TABBY => 'https://api.tabby.ai',
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
            self::AMAZON_PAYMENT_SERVICES => $isTestMode
                ? 'https://sbcheckout.payfort.com'
                : 'https://checkout.payfort.com',
            self::TAP => $isTestMode
                ? 'https://api.tap.company'
                : 'https://api.tap.company',
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

    /**
     * Check if this gateway supports Apple Pay.
     */
    public function supportsApplePay(): bool
    {
        return match($this) {
            self::AMAZON_PAYMENT_SERVICES, self::TAP, self::STRIPE => true,
            default => false,
        };
    }

    /**
     * Check if this gateway supports Google Pay.
     */
    public function supportsGooglePay(): bool
    {
        return match($this) {
            self::AMAZON_PAYMENT_SERVICES, self::TAP, self::STRIPE => true,
            default => false,
        };
    }

    /**
     * Get Apple Pay configuration fields for this gateway.
     */
    public function getApplePayFields(): array
    {
        if (! $this->supportsApplePay()) {
            return [];
        }

        return match($this) {
            self::AMAZON_PAYMENT_SERVICES => [
                'apple_pay_merchant_id' => [
                    'label' => 'Apple Pay Merchant ID',
                    'type' => 'text',
                    'required' => true,
                    'hint' => 'Your Apple Developer Merchant ID',
                ],
                'apple_pay_certificate' => [
                    'label' => 'Apple Pay Certificate',
                    'type' => 'textarea',
                    'required' => true,
                    'hint' => 'PEM-encoded merchant identity certificate',
                ],
                'apple_pay_key' => [
                    'label' => 'Apple Pay Private Key',
                    'type' => 'textarea',
                    'required' => true,
                    'hint' => 'PEM-encoded private key for the certificate',
                ],
                'apple_pay_domain' => [
                    'label' => 'Verified Domain',
                    'type' => 'text',
                    'required' => true,
                    'hint' => 'Domain verified with Apple Pay',
                ],
            ],

            self::TAP => [
                'apple_pay_merchant_id' => [
                    'label' => 'Apple Pay Merchant ID',
                    'type' => 'text',
                    'required' => true,
                    'hint' => 'Your Apple Developer Merchant ID (provided by Tap)',
                ],
                'apple_pay_domain' => [
                    'label' => 'Verified Domain',
                    'type' => 'text',
                    'required' => true,
                    'hint' => 'Domain verified with Apple Pay via Tap',
                ],
            ],

            self::STRIPE => [
                'apple_pay_domain' => [
                    'label' => 'Verified Domain',
                    'type' => 'text',
                    'required' => true,
                    'hint' => 'Domain registered in Stripe Dashboard for Apple Pay',
                ],
            ],

            default => [],
        };
    }

    /**
     * Get Google Pay configuration fields for this gateway.
     */
    public function getGooglePayFields(): array
    {
        if (! $this->supportsGooglePay()) {
            return [];
        }

        return match($this) {
            self::AMAZON_PAYMENT_SERVICES => [
                'google_pay_merchant_id' => [
                    'label' => 'Google Pay Merchant ID',
                    'type' => 'text',
                    'required' => true,
                    'hint' => 'Your Google Pay Merchant ID',
                ],
                'google_pay_merchant_name' => [
                    'label' => 'Merchant Display Name',
                    'type' => 'text',
                    'required' => true,
                    'hint' => 'Name displayed to customers during payment',
                ],
            ],

            self::TAP => [
                'google_pay_merchant_id' => [
                    'label' => 'Google Pay Merchant ID',
                    'type' => 'text',
                    'required' => false,
                    'hint' => 'Optional: Tap handles Google Pay integration',
                ],
            ],

            self::STRIPE => [],

            default => [],
        };
    }
}

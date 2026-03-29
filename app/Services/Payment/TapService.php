<?php

namespace App\Services\Payment;

use App\Enums\PaymentGatewayType;
use App\Exceptions\PspApiException;
use App\Models\CheckoutSession;
use App\Models\PaymentGateway;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TapService
{
    /**
     * Initialize a Tap payment session.
     *
     * @return array{charge_id: string, payment_url: string, psp_session_data: array}
     * @throws PspApiException
     */
    public function initializePayment(CheckoutSession $session, PaymentGateway $gateway): array
    {
        $config = $gateway->getServerConfig();
        $secretKey = $config['secret_key'] ?? null;

        if (! $secretKey) {
            throw new PspApiException('tap', 'Missing secret_key in gateway config');
        }

        $baseUrl = PaymentGatewayType::TAP->getApiUrl($gateway->is_test_mode);
        $payload = $this->buildChargePayload($session, $config);

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$secretKey}",
                'Content-Type' => 'application/json',
            ])
                ->timeout(30)
                ->post("{$baseUrl}/charges", $payload);
        } catch (\Throwable $e) {
            Log::error('Tap API connection error', [
                'session_uuid' => $session->session_uuid,
                'error' => $e->getMessage(),
            ]);
            throw new PspApiException('tap', 'Connection error: ' . $e->getMessage(), null, 0, $e);
        }

        if (! $response->successful()) {
            Log::error('Tap API error response', [
                'session_uuid' => $session->session_uuid,
                'status' => $response->status(),
                'body' => $response->json(),
            ]);
            throw new PspApiException('tap', 'API returned ' . $response->status(), $response->json());
        }

        $data = $response->json();
        $chargeId = $data['id'] ?? null;
        $paymentUrl = $data['transaction']['url'] ?? null;

        if (! $chargeId || ! $paymentUrl) {
            throw new PspApiException('tap', 'Missing charge_id or payment_url in response', $data);
        }

        return [
            'charge_id' => $chargeId,
            'payment_url' => $paymentUrl,
            'psp_session_data' => $data,
        ];
    }

    /**
     * Verify a Tap payment status.
     *
     * @return string normalized status: authorized|captured|failed|pending
     */
    public function verifyPayment(string $chargeId, PaymentGateway $gateway): string
    {
        $config = $gateway->getServerConfig();
        $secretKey = $config['secret_key'] ?? null;
        $baseUrl = PaymentGatewayType::TAP->getApiUrl($gateway->is_test_mode);

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$secretKey}",
            ])
                ->timeout(30)
                ->get("{$baseUrl}/charges/{$chargeId}");
        } catch (\Throwable $e) {
            Log::error('Tap verify connection error', ['charge_id' => $chargeId, 'error' => $e->getMessage()]);
            throw new PspApiException('tap', 'Verification connection error', null, 0, $e);
        }

        if (! $response->successful()) {
            throw new PspApiException('tap', 'Verification failed with status ' . $response->status(), $response->json());
        }

        $data = $response->json();
        $status = strtolower($data['status'] ?? '');

        return match ($status) {
            'authorized', 'approved' => 'authorized',
            'captured', 'paid' => 'captured',
            'failed', 'cancelled', 'declined', 'voided' => 'failed',
            'initiated', 'pending', 'processing' => 'pending',
            default => 'pending',
        };
    }

    /**
     * Create a refund for a Tap charge.
     *
     * @return array{refund_id: string, status: string}
     * @throws PspApiException
     */
    public function createRefund(string $chargeId, float $amount, PaymentGateway $gateway): array
    {
        $config = $gateway->getServerConfig();
        $secretKey = $config['secret_key'] ?? null;
        $baseUrl = PaymentGatewayType::TAP->getApiUrl($gateway->is_test_mode);

        $payload = [
            'charge_id' => $chargeId,
            'amount' => $amount,
            'currency' => 'SAR', // Default currency, should be dynamic based on original charge
            'reason' => 'Customer requested refund',
            'metadata' => [
                'refund_source' => 'oliv_platform',
                'created_at' => now()->toISOString(),
            ],
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$secretKey}",
                'Content-Type' => 'application/json',
            ])
                ->timeout(30)
                ->post("{$baseUrl}/refunds", $payload);
        } catch (\Throwable $e) {
            Log::error('Tap refund connection error', ['charge_id' => $chargeId, 'error' => $e->getMessage()]);
            throw new PspApiException('tap', 'Refund connection error', null, 0, $e);
        }

        if (! $response->successful()) {
            Log::error('Tap refund error response', [
                'charge_id' => $chargeId,
                'status' => $response->status(),
                'body' => $response->json(),
            ]);
            throw new PspApiException('tap', 'Refund API returned ' . $response->status(), $response->json());
        }

        $data = $response->json();
        $refundId = $data['id'] ?? null;
        $status = strtolower($data['status'] ?? '');

        return [
            'refund_id' => $refundId,
            'status' => $status,
            'psp_session_data' => $data,
        ];
    }

    private function buildChargePayload(CheckoutSession $session, array $config): array
    {
        $lineItems = collect($session->line_items)->map(fn(array $item) => [
            'name' => $item['title'] ?? $item['variant_id'] ?? 'Item',
            'description' => $item['description'] ?? '',
            'amount' => (int) (($item['price'] ?? 0) * 100), // Tap expects amount in cents/fils
            'currency' => $session->currency,
            'quantity' => $item['quantity'] ?? 1,
            'images' => $item['images'] ?? [],
        ])->all();

        return [
            'amount' => (int) ($session->amount * 100), // Convert to minor units
            'currency' => $session->currency,
            'threeDSecure' => true,
            'save_card' => false,
            'description' => 'Order ' . $session->session_uuid,
            'statement_descriptor' => 'OLIV',
            'metadata' => [
                'session_uuid' => $session->session_uuid,
                'customer_email' => $session->customer_email,
                'platform' => 'oliv',
            ],
            'reference' => [
                'transaction' => 'txn_' . $session->session_uuid,
                'order' => 'ORD_' . $session->session_uuid,
            ],
            'receipt' => [
                'email' => true,
                'sms' => false,
            ],
            'customer' => [
                'first_name' => $session->shipping_address['first_name'] ?? '',
                'last_name' => $session->shipping_address['last_name'] ?? '',
                'email' => $session->customer_email,
                'phone' => [
                    'country_code' => $session->shipping_address['country_code'] ?? '965',
                    'number' => $this->extractPhoneNumber($session->customer_phone),
                ],
            ],
            'source' => [
                'id' => 'src_all',
            ],
            'post' => [
                'url' => config('app.url') . '/api/v1/payments/tap/callback?session=' . $session->session_uuid,
            ],
            'redirect' => [
                'url' => config('app.url') . '/api/v1/payments/tap/callback?session=' . $session->session_uuid,
            ],
            'merchant' => [
                'id' => $config['merchant_id'] ?? null,
            ],
            'products' => $lineItems,
        ];
    }

    /**
     * Extract phone number without country code.
     */
    private function extractPhoneNumber(?string $phone): string
    {
        if (! $phone) {
            return '';
        }

        // Remove country code, spaces, dashes, parentheses
        return preg_replace('/^(\+?\d{1,3}[\s-]?)?[\s\-\(\)]*/', '', $phone);
    }

    /**
     * Initialize an Apple Pay payment with Tap.
     *
     * @return array{charge_id: string, payment_url: string, psp_session_data: array}
     * @throws PspApiException
     */
    public function initializeApplePayPayment(CheckoutSession $session, PaymentGateway $gateway, array $applePayToken): array
    {
        $config = $gateway->getServerConfig();
        $secretKey = $config['secret_key'] ?? null;

        if (! $secretKey) {
            throw new PspApiException('tap', 'Missing secret_key in gateway config');
        }

        $baseUrl = PaymentGatewayType::TAP->getApiUrl($gateway->is_test_mode);
        $payload = $this->buildApplePayChargePayload($session, $config, $applePayToken);

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$secretKey}",
                'Content-Type' => 'application/json',
            ])
                ->timeout(30)
                ->post("{$baseUrl}/charges", $payload);
        } catch (\Throwable $e) {
            Log::error('Tap Apple Pay API connection error', [
                'session_uuid' => $session->session_uuid,
                'error' => $e->getMessage(),
            ]);
            throw new PspApiException('tap', 'Connection error: ' . $e->getMessage(), null, 0, $e);
        }

        if (! $response->successful()) {
            Log::error('Tap Apple Pay API error response', [
                'session_uuid' => $session->session_uuid,
                'status' => $response->status(),
                'body' => $response->json(),
            ]);
            throw new PspApiException('tap', 'API returned ' . $response->status(), $response->json());
        }

        $data = $response->json();
        $chargeId = $data['id'] ?? null;
        $status = strtolower($data['status'] ?? '');

        if (! $chargeId) {
            throw new PspApiException('tap', 'Missing charge_id in response', $data);
        }

        return [
            'charge_id' => $chargeId,
            'status' => $status,
            'psp_session_data' => $data,
        ];
    }

    private function buildApplePayChargePayload(CheckoutSession $session, array $config, array $applePayToken): array
    {
        return [
            'amount' => $session->amount,
            'currency' => $session->currency,
            'threeDSecure' => false, // Apple Pay handles authentication
            'save_card' => false,
            'description' => 'Order ' . $session->session_uuid,
            'statement_descriptor' => 'OLIV',
            'metadata' => [
                'session_uuid' => $session->session_uuid,
                'payment_method' => 'apple_pay',
            ],
            'reference' => [
                'transaction' => 'txn_' . $session->session_uuid,
                'order' => 'ORD_' . $session->session_uuid,
            ],
            'receipt' => [
                'email' => true,
                'sms' => false,
            ],
            'customer' => [
                'first_name' => $session->shipping_address['first_name'] ?? '',
                'last_name' => $session->shipping_address['last_name'] ?? '',
                'email' => $session->customer_email,
            ],
            'source' => [
                'id' => 'src_apple_pay',
                'token' => $applePayToken,
            ],
            'post' => [
                'url' => config('app.url') . '/api/v1/payments/tap/apple-pay/callback?session=' . $session->session_uuid,
            ],
            'redirect' => [
                'url' => config('app.url') . '/api/v1/payments/tap/apple-pay/callback?session=' . $session->session_uuid,
            ],
            'merchant' => [
                'id' => $config['merchant_id'] ?? null,
            ],
        ];
    }
}

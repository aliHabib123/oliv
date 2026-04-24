<?php

namespace App\Services\Payment;

use App\Enums\PaymentGatewayType;
use App\Exceptions\PspApiException;
use App\Models\CheckoutSession;
use App\Models\PaymentGateway;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TabbyService
{
    /**
     * Initialize a Tabby payment session.
     *
     * @return array{payment_id: string, web_url: string, psp_session_data: array}
     * @throws PspApiException
     */
    public function initializePayment(CheckoutSession $session, PaymentGateway $gateway): array
    {
        $config = $gateway->getServerConfig();
        $secretKey = $config['secret_key'] ?? null;

        if (! $secretKey) {
            throw new PspApiException('tabby', 'Missing secret_key in gateway config');
        }

        $baseUrl = PaymentGatewayType::TABBY->getApiUrl($gateway->is_test_mode);

        $payload = $this->buildCreateSessionPayload($session, $config);

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$secretKey}",
                'Content-Type' => 'application/json',
            ])
                ->timeout(30)
                ->post("{$baseUrl}/api/v2/checkout", $payload);
        } catch (\Throwable $e) {
            Log::error('Tabby API connection error', [
                'session_uuid' => $session->session_uuid,
                'error' => $e->getMessage(),
            ]);
            throw new PspApiException('tabby', 'Connection error: ' . $e->getMessage(), null, 0, $e);
        }

        if (! $response->successful()) {
            Log::error('Tabby API error response', [
                'session_uuid' => $session->session_uuid,
                'status' => $response->status(),
                'body' => $response->json(),
            ]);
            throw new PspApiException('tabby', 'API returned ' . $response->status(), $response->json());
        }

        $data = $response->json();
        $paymentId = $data['payment']['id'] ?? $data['id'] ?? null;
        $webUrl = $data['configuration']['available_products']['installments'][0]['web_url']
            ?? $data['checkout_url'] ?? null;

        if (! $paymentId || ! $webUrl) {
            throw new PspApiException('tabby', 'Missing payment_id or web_url in response', $data);
        }

        return [
            'payment_id' => $paymentId,
            'web_url' => $webUrl,
            'psp_session_data' => $data,
        ];
    }

    /**
     * Verify a Tabby payment status.
     *
     * @return string normalized status: authorized|captured|failed|pending
     */
    public function verifyPayment(string $paymentId, PaymentGateway $gateway): string
    {
        $config = $gateway->getServerConfig();
        $secretKey = $config['secret_key'] ?? null;
        $baseUrl = PaymentGatewayType::TABBY->getApiUrl($gateway->is_test_mode);

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$secretKey}",
            ])
                ->timeout(30)
                ->get("{$baseUrl}/api/v2/payments/{$paymentId}");
        } catch (\Throwable $e) {
            Log::error('Tabby verify connection error', ['payment_id' => $paymentId, 'error' => $e->getMessage()]);
            throw new PspApiException('tabby', 'Verification connection error', null, 0, $e);
        }

        Log::info('Tabby verifyPayment response', [
            'payment_id' => $paymentId,
            'http_status' => $response->status(),
            'raw_body' => $response->body(),
            'json_body' => $response->json(),
            'headers' => $response->headers(),
            'url' => "{$baseUrl}/api/v2/payments/{$paymentId}",
        ]);

        if (! $response->successful()) {
            throw new PspApiException('tabby', 'Verification failed with status ' . $response->status(), $response->json());
        }

        $data = $response->json();
        $status = strtolower($data['status'] ?? '');

        Log::info('Tabby verifyPayment mapped status', [
            'payment_id' => $paymentId,
            'raw_status' => $data['status'] ?? null,
            'normalized_status' => $status,
        ]);

        return match ($status) {
            'authorized', 'approved' => 'authorized',
            'closed', 'captured' => 'captured',
            'rejected', 'expired', 'error' => 'failed',
            default => 'pending',
        };
    }

    private function buildCreateSessionPayload(CheckoutSession $session, array $config): array
    {
        $lineItems = collect($session->line_items)->map(fn(array $item) => [
            'title' => $item['title'] ?? $item['variant_id'] ?? 'Item',
            'quantity' => $item['quantity'],
            'unit_price' => number_format(($item['price'] ?? 0), 2, '.', ''),
            'category' => $item['category'] ?? 'general',
        ])->all();

        return [
            'payment' => [
                'amount' => $session->amount,
                'currency' => $session->currency,
                'buyer' => [
                    'email' => $session->customer_email,
                    'phone' => $session->customer_phone,
                    'name' => trim(
                        ($session->shipping_address['first_name'] ?? '') . ' ' .
                        ($session->shipping_address['last_name'] ?? '')
                    ),
                ],
                'shipping_address' => [
                    'address' => $session->shipping_address['address1'] ?? '',
                    'city' => $session->shipping_address['city'] ?? '',
                    'zip' => $session->shipping_address['zip'] ?? '',
                ],
                'order' => [
                    'reference_id' => $session->session_uuid,
                    'items' => $lineItems,
                ],
            ],
            'lang' => 'en',
            'merchant_code' => $config['merchant_code'] ?? 'default',
            'merchant_urls' => [
                'success' => config('app.url') . '/api/v1/payments/tabby/callback?status=success&session=' . $session->session_uuid,
                'cancel' => config('app.url') . '/api/v1/payments/tabby/callback?status=cancel&session=' . $session->session_uuid,
                'failure' => config('app.url') . '/api/v1/payments/tabby/callback?status=failure&session=' . $session->session_uuid,
            ],
        ];
    }
}

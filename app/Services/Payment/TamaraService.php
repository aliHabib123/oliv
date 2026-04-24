<?php

namespace App\Services\Payment;

use App\Enums\PaymentGatewayType;
use App\Exceptions\PspApiException;
use App\Models\CheckoutSession;
use App\Models\PaymentGateway;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TamaraService
{
    /**
     * Initialize a Tamara payment order.
     *
     * @return array{order_id: string, checkout_url: string, psp_session_data: array}
     * @throws PspApiException
     */
    public function initializePayment(CheckoutSession $session, PaymentGateway $gateway): array
    {
        $config = $gateway->getServerConfig();
        $apiToken = $config['api_token'] ?? null;

        if (! $apiToken) {
            throw new PspApiException('tamara', 'Missing api_token in gateway config');
        }

        $baseUrl = PaymentGatewayType::TAMARA->getApiUrl($gateway->is_test_mode);
        $payload = $this->buildCreateOrderPayload($session, $config);

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiToken}",
                'Content-Type' => 'application/json',
            ])
                ->timeout(30)
                ->post("{$baseUrl}/checkout", $payload);
        } catch (\Throwable $e) {
            Log::error('Tamara API connection error', [
                'session_uuid' => $session->session_uuid,
                'error' => $e->getMessage(),
            ]);
            throw new PspApiException('tamara', 'Connection error: ' . $e->getMessage(), null, 0, $e);
        }

        if (! $response->successful()) {
            Log::error('Tamara API error response', [
                'session_uuid' => $session->session_uuid,
                'status' => $response->status(),
                'body' => $response->json(),
            ]);
            throw new PspApiException('tamara', 'API returned ' . $response->status(), $response->json());
        }

        $data = $response->json();
        $orderId = $data['order_id'] ?? null;
        $checkoutUrl = $data['checkout_url'] ?? null;

        if (! $orderId || ! $checkoutUrl) {
            throw new PspApiException('tamara', 'Missing order_id or checkout_url in response', $data);
        }

        return [
            'order_id' => $orderId,
            'checkout_url' => $checkoutUrl,
            'psp_session_data' => $data,
        ];
    }

    /**
     * Verify a Tamara payment/order status.
     *
     * @return string normalized status: authorized|captured|failed|pending
     */
    public function verifyPayment(string $orderId, PaymentGateway $gateway): string
    {
        $config = $gateway->getServerConfig();
        $apiToken = $config['api_token'] ?? null;
        $baseUrl = PaymentGatewayType::TAMARA->getApiUrl($gateway->is_test_mode);

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiToken}",
            ])
                ->timeout(30)
                ->get("{$baseUrl}/orders/{$orderId}");
        } catch (\Throwable $e) {
            Log::error('Tamara verify connection error', ['order_id' => $orderId, 'error' => $e->getMessage()]);
            throw new PspApiException('tamara', 'Verification connection error', null, 0, $e);
        }

        Log::info('Tamara verifyPayment response', [
            'order_id' => $orderId,
            'http_status' => $response->status(),
            'body' => $response->json(),
            'url' => "{$baseUrl}/orders/{$orderId}",
        ]);

        if (! $response->successful()) {
            throw new PspApiException('tamara', 'Verification failed with status ' . $response->status(), $response->json());
        }

        $data = $response->json();
        $status = strtolower($data['status'] ?? '');

        Log::info('Tamara verifyPayment mapped status', [
            'order_id' => $orderId,
            'raw_status' => $data['status'] ?? null,
            'normalized_status' => $status,
        ]);

        return match ($status) {
            'approved', 'authorised', 'authorized' => 'authorized',
            'fully_captured', 'captured' => 'captured',
            'declined', 'expired', 'canceled' => 'failed',
            default => 'pending',
        };
    }

    private function buildCreateOrderPayload(CheckoutSession $session, array $config): array
    {
        $lineItems = collect($session->line_items)->map(fn(array $item) => [
            'name' => $item['title'] ?? $item['variant_id'] ?? 'Item',
            'quantity' => $item['quantity'],
            'unit_price' => [
                'amount' => number_format(($item['price'] ?? 0), 2, '.', ''),
                'currency' => $session->currency,
            ],
            'total_amount' => [
                'amount' => number_format(($item['price'] ?? 0) * ($item['quantity'] ?? 1), 2, '.', ''),
                'currency' => $session->currency,
            ],
            'reference_id' => $item['variant_id'] ?? '',
            'sku' => $item['sku'] ?? $item['variant_id'] ?? '',
        ])->all();

        return [
            'order_reference_id' => $session->session_uuid,
            'total_amount' => [
                'amount' => $session->amount,
                'currency' => $session->currency,
            ],
            'description' => 'Order ' . $session->session_uuid,
            'country_code' => $session->shipping_address['country_code'] ?? 'AE',
            'payment_type' => 'PAY_BY_INSTALMENTS',
            'locale' => 'en_US',
            'items' => $lineItems,
            'consumer' => [
                'email' => $session->customer_email,
                'phone_number' => $session->customer_phone,
                'first_name' => $session->shipping_address['first_name'] ?? '',
                'last_name' => $session->shipping_address['last_name'] ?? '',
            ],
            'shipping_address' => [
                'first_name' => $session->shipping_address['first_name'] ?? '',
                'last_name' => $session->shipping_address['last_name'] ?? '',
                'line1' => $session->shipping_address['address1'] ?? '',
                'city' => $session->shipping_address['city'] ?? '',
                'country_code' => $session->shipping_address['country_code'] ?? 'AE',
                'phone_number' => $session->shipping_address['phone'] ?? $session->customer_phone,
            ],
            'merchant_url' => [
                'success' => config('app.url') . '/api/v1/payments/tamara/callback?status=success&session=' . $session->session_uuid,
                'failure' => config('app.url') . '/api/v1/payments/tamara/callback?status=failure&session=' . $session->session_uuid,
                'cancel' => config('app.url') . '/api/v1/payments/tamara/callback?status=cancel&session=' . $session->session_uuid,
                'notification' => config('app.url') . '/webhooks/tamara',
            ],
        ];
    }
}

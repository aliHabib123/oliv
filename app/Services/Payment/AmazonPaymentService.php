<?php

namespace App\Services\Payment;

use App\Enums\PaymentGatewayType;
use App\Exceptions\PspApiException;
use App\Models\CheckoutSession;
use App\Models\PaymentGateway;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AmazonPaymentService
{
    /**
     * Initialize an Amazon Payment Services (PayFort) payment.
     *
     * @return array{fort_id: string, payment_url: string, psp_session_data: array}
     * @throws PspApiException
     */
    public function initializePayment(CheckoutSession $session, PaymentGateway $gateway): array
    {
        $config = $gateway->getServerConfig();
        $merchantIdentifier = $config['merchant_identifier'] ?? null;
        $accessCode = $config['access_code'] ?? null;
        $shaRequestPhrase = $config['sha_request_phrase'] ?? null;

        if (! $merchantIdentifier || ! $accessCode || ! $shaRequestPhrase) {
            throw new PspApiException('amazon_payment_services', 'Missing required credentials in gateway config');
        }

        $baseUrl = PaymentGatewayType::AMAZON_PAYMENT_SERVICES->getApiUrl($gateway->is_test_mode);
        $payload = $this->buildTokenizationPayload($session, $config, $gateway->is_test_mode);

        return [
            'merchant_identifier' => $merchantIdentifier,
            'access_code' => $accessCode,
            'payment_url' => $baseUrl . '/FortAPI/paymentPage',
            'tokenization_url' => $baseUrl . '/FortAPI/paymentApi',
            'request_params' => $payload,
            'psp_session_data' => [
                'merchant_reference' => $payload['merchant_reference'],
                'amount' => $session->amount,
                'currency' => $session->currency,
            ],
        ];
    }

    /**
     * Create a charge/purchase request to APS.
     *
     * @return array{fort_id: string, status: string, response_code: string, psp_session_data: array}
     * @throws PspApiException
     */
    public function createCharge(CheckoutSession $session, PaymentGateway $gateway, string $tokenName): array
    {
        $config = $gateway->getServerConfig();
        $baseUrl = PaymentGatewayType::AMAZON_PAYMENT_SERVICES->getApiUrl($gateway->is_test_mode);

        $payload = $this->buildPurchasePayload($session, $config, $tokenName);

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])
                ->timeout(30)
                ->post("{$baseUrl}/FortAPI/paymentApi", $payload);
        } catch (\Throwable $e) {
            Log::error('APS API connection error', [
                'session_uuid' => $session->session_uuid,
                'error' => $e->getMessage(),
            ]);
            throw new PspApiException('amazon_payment_services', 'Connection error: ' . $e->getMessage(), null, 0, $e);
        }

        $data = $response->json();
        $responseCode = $data['response_code'] ?? '';
        $status = $data['status'] ?? '';

        // APS response codes: 14000 = success, 20064 = 3DS required
        if (! in_array($responseCode, ['14000', '20064'])) {
            Log::error('APS API error response', [
                'session_uuid' => $session->session_uuid,
                'response_code' => $responseCode,
                'response_message' => $data['response_message'] ?? '',
                'body' => $data,
            ]);
            throw new PspApiException('amazon_payment_services', $data['response_message'] ?? 'Payment failed', $data);
        }

        return [
            'fort_id' => $data['fort_id'] ?? '',
            'status' => $status,
            'response_code' => $responseCode,
            '3ds_url' => $data['3ds_url'] ?? null,
            'psp_session_data' => $data,
        ];
    }

    /**
     * Verify an APS payment status.
     *
     * @return string normalized status: authorized|captured|failed|pending
     */
    public function verifyPayment(string $fortId, PaymentGateway $gateway): string
    {
        $config = $gateway->getServerConfig();
        $baseUrl = PaymentGatewayType::AMAZON_PAYMENT_SERVICES->getApiUrl($gateway->is_test_mode);

        $payload = [
            'query_command' => 'CHECK_STATUS',
            'merchant_identifier' => $config['merchant_identifier'],
            'access_code' => $config['access_code'],
            'merchant_reference' => $fortId,
            'language' => 'en',
        ];

        $payload['signature'] = $this->generateSignature($payload, $config['sha_request_phrase'], $config['sha_type'] ?? 'SHA-256');

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])
                ->timeout(30)
                ->post("{$baseUrl}/FortAPI/paymentApi", $payload);
        } catch (\Throwable $e) {
            Log::error('APS verify connection error', ['fort_id' => $fortId, 'error' => $e->getMessage()]);
            throw new PspApiException('amazon_payment_services', 'Verification connection error', null, 0, $e);
        }

        $data = $response->json();
        $transactionStatus = strtolower($data['transaction_status'] ?? '');
        $responseCode = $data['response_code'] ?? '';

        // Response codes: 14000 = success
        if ($responseCode === '14000') {
            return match ($transactionStatus) {
                '02', 'authorized' => 'authorized',
                '04', 'captured', 'purchase' => 'captured',
                default => 'pending',
            };
        }

        return 'failed';
    }

    /**
     * Generate an SDK token for mobile SDK integration.
     *
     * @return array{sdk_token: string, merchant_identifier: string, access_code: string}
     * @throws PspApiException
     */
    public function generateSdkToken(CheckoutSession $session, PaymentGateway $gateway, string $deviceId): array
    {
        $config = $gateway->getServerConfig();
        $merchantIdentifier = $config['merchant_identifier'] ?? null;
        $accessCode = $config['access_code'] ?? null;
        $shaRequestPhrase = $config['sha_request_phrase'] ?? null;

        if (! $merchantIdentifier || ! $accessCode || ! $shaRequestPhrase) {
            throw new PspApiException('amazon_payment_services', 'Missing required credentials in gateway config');
        }

        $baseUrl = PaymentGatewayType::AMAZON_PAYMENT_SERVICES->getApiUrl($gateway->is_test_mode);

        $payload = [
            'service_command' => 'SDK_TOKEN',
            'merchant_identifier' => $merchantIdentifier,
            'access_code' => $accessCode,
            'device_id' => $deviceId,
            'language' => 'en',
        ];

        $payload['signature'] = $this->generateSignature($payload, $shaRequestPhrase, $config['sha_type'] ?? 'SHA-256');

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])
                ->timeout(30)
                ->post("{$baseUrl}/FortAPI/paymentApi", $payload);
        } catch (\Throwable $e) {
            Log::error('APS SDK token API connection error', [
                'session_uuid' => $session->session_uuid,
                'error' => $e->getMessage(),
            ]);
            throw new PspApiException('amazon_payment_services', 'Connection error: ' . $e->getMessage(), null, 0, $e);
        }

        $data = $response->json();
        $responseCode = $data['response_code'] ?? '';

        if ($responseCode !== '22000') {
            Log::error('APS SDK token error response', [
                'session_uuid' => $session->session_uuid,
                'response_code' => $responseCode,
                'response_message' => $data['response_message'] ?? '',
                'body' => $data,
            ]);
            throw new PspApiException('amazon_payment_services', $data['response_message'] ?? 'Failed to generate SDK token', $data);
        }

        return [
            'sdk_token' => $data['sdk_token'] ?? '',
            'merchant_identifier' => $merchantIdentifier,
            'access_code' => $accessCode,
        ];
    }

    /**
     * Verify the response signature from APS.
     */
    public function verifyResponseSignature(array $response, PaymentGateway $gateway): bool
    {
        $config = $gateway->getServerConfig();
        $shaResponsePhrase = $config['sha_response_phrase'] ?? '';
        $shaType = $config['sha_type'] ?? 'SHA-256';

        $receivedSignature = $response['signature'] ?? '';
        unset($response['signature']);

        $calculatedSignature = $this->generateSignature($response, $shaResponsePhrase, $shaType);

        return hash_equals($calculatedSignature, $receivedSignature);
    }

    private function buildTokenizationPayload(CheckoutSession $session, array $config, bool $isTestMode): array
    {
        $merchantReference = 'ORD-' . $session->session_uuid;
        $amount = (int) ($session->amount * 100); // APS expects amount in minor units

        $payload = [
            'service_command' => 'TOKENIZATION',
            'merchant_identifier' => $config['merchant_identifier'],
            'access_code' => $config['access_code'],
            'merchant_reference' => $merchantReference,
            'language' => 'en',
            'return_url' => config('app.url') . '/api/v1/payments/aps/callback?session=' . $session->session_uuid,
        ];

        $payload['signature'] = $this->generateSignature($payload, $config['sha_request_phrase'], $config['sha_type'] ?? 'SHA-256');

        return $payload;
    }

    private function buildPurchasePayload(CheckoutSession $session, array $config, string $tokenName): array
    {
        $merchantReference = 'ORD-' . $session->session_uuid;
        $amount = (int) ($session->amount * 100);

        $payload = [
            'command' => 'PURCHASE',
            'merchant_identifier' => $config['merchant_identifier'],
            'access_code' => $config['access_code'],
            'merchant_reference' => $merchantReference,
            'amount' => (string) $amount,
            'currency' => $session->currency,
            'language' => 'en',
            'customer_email' => $session->customer_email,
            'token_name' => $tokenName,
            'return_url' => config('app.url') . '/api/v1/payments/aps/callback?session=' . $session->session_uuid,
        ];

        if (! empty($session->customer_phone)) {
            $payload['phone_number'] = $session->customer_phone;
        }

        $payload['signature'] = $this->generateSignature($payload, $config['sha_request_phrase'], $config['sha_type'] ?? 'SHA-256');

        return $payload;
    }

    /**
     * Generate SHA signature for APS request/response.
     */
    private function generateSignature(array $params, string $shaPhrase, string $shaType = 'SHA-256'): string
    {
        ksort($params);

        $signatureString = $shaPhrase;
        foreach ($params as $key => $value) {
            if ($key === 'signature' || $value === '' || $value === null) {
                continue;
            }
            $signatureString .= $key . '=' . $value;
        }
        $signatureString .= $shaPhrase;

        $algorithm = match (strtoupper($shaType)) {
            'SHA-512' => 'sha512',
            default => 'sha256',
        };

        return hash($algorithm, $signatureString);
    }

    /**
     * Initialize an Apple Pay payment with APS.
     *
     * @return array{fort_id: string, status: string, psp_session_data: array}
     * @throws PspApiException
     */
    public function initializeApplePayPayment(CheckoutSession $session, PaymentGateway $gateway, array $applePayData): array
    {
        $config = $gateway->getServerConfig();
        $walletConfig = $gateway->wallet_config ?? [];

        $merchantIdentifier = $config['merchant_identifier'] ?? null;
        $accessCode = $config['access_code'] ?? null;

        if (! $merchantIdentifier || ! $accessCode) {
            throw new PspApiException('amazon_payment_services', 'Missing required credentials in gateway config');
        }

        $baseUrl = PaymentGatewayType::AMAZON_PAYMENT_SERVICES->getApiUrl($gateway->is_test_mode);
        $payload = $this->buildApplePayPayload($session, $config, $walletConfig, $applePayData);

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])
                ->timeout(30)
                ->post("{$baseUrl}/FortAPI/paymentApi", $payload);
        } catch (\Throwable $e) {
            Log::error('APS Apple Pay API connection error', [
                'session_uuid' => $session->session_uuid,
                'error' => $e->getMessage(),
            ]);
            throw new PspApiException('amazon_payment_services', 'Connection error: ' . $e->getMessage(), null, 0, $e);
        }

        $data = $response->json();
        $responseCode = $data['response_code'] ?? '';
        $status = $data['status'] ?? '';

        if ($responseCode !== '14000') {
            Log::error('APS Apple Pay API error response', [
                'session_uuid' => $session->session_uuid,
                'response_code' => $responseCode,
                'response_message' => $data['response_message'] ?? '',
                'body' => $data,
            ]);
            throw new PspApiException('amazon_payment_services', $data['response_message'] ?? 'Apple Pay payment failed', $data);
        }

        return [
            'fort_id' => $data['fort_id'] ?? '',
            'status' => $status,
            'response_code' => $responseCode,
            'psp_session_data' => $data,
        ];
    }

    /**
     * Create Apple Pay merchant session for domain validation.
     */
    public function createApplePaySession(PaymentGateway $gateway, string $validationUrl): array
    {
        $walletConfig = $gateway->wallet_config ?? [];

        $merchantId = $walletConfig['apple_pay_merchant_id'] ?? null;
        $certificate = $walletConfig['apple_pay_certificate'] ?? null;
        $privateKey = $walletConfig['apple_pay_key'] ?? null;
        $domain = $walletConfig['apple_pay_domain'] ?? null;

        if (! $merchantId || ! $certificate || ! $privateKey || ! $domain) {
            throw new PspApiException('amazon_payment_services', 'Missing Apple Pay configuration');
        }

        // Create temporary certificate files
        $certFile = tempnam(sys_get_temp_dir(), 'apple_cert_');
        $keyFile = tempnam(sys_get_temp_dir(), 'apple_key_');

        file_put_contents($certFile, $certificate);
        file_put_contents($keyFile, $privateKey);

        try {
            $response = Http::withOptions([
                'cert' => $certFile,
                'ssl_key' => $keyFile,
            ])
                ->timeout(30)
                ->post($validationUrl, [
                    'merchantIdentifier' => $merchantId,
                    'displayName' => 'Oliv',
                    'initiative' => 'web',
                    'initiativeContext' => $domain,
                ]);

            return $response->json();
        } catch (\Throwable $e) {
            Log::error('Apple Pay session creation failed', ['error' => $e->getMessage()]);
            throw new PspApiException('amazon_payment_services', 'Apple Pay session creation failed: ' . $e->getMessage());
        } finally {
            @unlink($certFile);
            @unlink($keyFile);
        }
    }

    private function buildApplePayPayload(CheckoutSession $session, array $config, array $walletConfig, array $applePayData): array
    {
        $merchantReference = 'ORD-' . $session->session_uuid;
        $amount = (int) ($session->amount * 100);

        $payload = [
            'command' => 'PURCHASE',
            'merchant_identifier' => $config['merchant_identifier'],
            'access_code' => $config['access_code'],
            'merchant_reference' => $merchantReference,
            'amount' => (string) $amount,
            'currency' => $session->currency,
            'language' => 'en',
            'customer_email' => $session->customer_email,
            'digital_wallet' => 'APPLE_PAY',
            'apple_data' => $applePayData['paymentData']['data'] ?? '',
            'apple_signature' => $applePayData['paymentData']['signature'] ?? '',
            'apple_header' => json_encode($applePayData['paymentData']['header'] ?? []),
            'apple_paymentMethod' => json_encode($applePayData['paymentMethod'] ?? []),
            'return_url' => config('app.url') . '/api/v1/payments/aps/apple-pay/callback?session=' . $session->session_uuid,
        ];

        $payload['signature'] = $this->generateSignature($payload, $config['sha_request_phrase'], $config['sha_type'] ?? 'SHA-256');

        return $payload;
    }
}

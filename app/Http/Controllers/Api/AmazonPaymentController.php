<?php

namespace App\Http\Controllers\Api;

use App\Enums\PaymentGatewayType;
use App\Exceptions\PspApiException;
use App\Http\Controllers\Controller;
use App\Models\CheckoutSession;
use App\Models\PaymentGateway;
use App\Models\PaymentTransaction;
use App\Services\CheckoutSessionService;
use App\Services\Payment\AmazonPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

class AmazonPaymentController extends Controller
{
    public function __construct(
        private AmazonPaymentService $apsService,
        private CheckoutSessionService $sessionService,
    ) {}

    #[OA\Post(
        path: '/v1/payments/aps/initialize',
        tags: ['Payment'],
        summary: 'Initialize Amazon Payment Services (PayFort) payment for a checkout session',
        security: [['AppKey' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['session_id'],
            properties: [
                new OA\Property(property: 'session_id', type: 'string', format: 'uuid'),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'APS payment session created')]
    #[OA\Response(response: 409, description: 'Session not in pending state')]
    #[OA\Response(response: 502, description: 'APS API error')]
    public function initialize(Request $request): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');
        if (! $tenant) {
            return response()->json(['ok' => false, 'error' => 'Tenant not found'], 404);
        }

        $validated = $request->validate([
            'session_id' => 'required|string',
        ]);

        $session = $this->sessionService->findByUuid($tenant, $validated['session_id']);
        if (! $session) {
            return response()->json(['ok' => false, 'error' => 'Session not found'], 404);
        }

        if (! $session->isInState(CheckoutSession::STATUS_PENDING)) {
            return response()->json([
                'ok' => false,
                'error' => 'Session is not in pending state',
                'current_status' => $session->status,
            ], 409);
        }

        $gateway = $tenant->paymentGateways()
            ->where('gateway_type', 'amazon_payment_services')
            ->where('is_enabled', true)
            ->first();

        if (! $gateway) {
            return response()->json([
                'ok' => false,
                'error' => 'Amazon Payment Services not configured for this tenant',
                'fallback_checkout_url' => $session->fallback_url,
            ], 400);
        }

        try {
            $result = $this->apsService->initializePayment($session, $gateway);

            $transaction = PaymentTransaction::create([
                'tenant_id' => $tenant->id,
                'checkout_session_id' => $session->id,
                'psp_type' => 'amazon_payment_services',
                'psp_transaction_id' => $result['merchant_reference'],
                'psp_session_data' => $result['psp_session_data'],
                'status' => PaymentTransaction::STATUS_PENDING,
                'amount' => $session->amount,
                'currency' => $session->currency,
                'idempotency_key' => Str::uuid()->toString(),
            ]);

            $this->sessionService->transition($session, CheckoutSession::STATUS_PAYMENT_INITIATED);
            $session->update(['psp_type' => 'amazon_payment_services']);

            return response()->json([
                'ok' => true,
                'merchant_identifier' => $result['merchant_identifier'],
                'access_code' => $result['access_code'],
                'payment_url' => $result['payment_url'],
                'tokenization_url' => $result['tokenization_url'],
                'request_params' => $result['request_params'],
                'psp_type' => 'amazon_payment_services',
            ]);
        } catch (PspApiException $e) {
            Log::error('APS initialization failed', [
                'session_uuid' => $session->session_uuid,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'ok' => false,
                'error' => 'Amazon Payment Services initialization failed',
                'fallback_checkout_url' => $session->fallback_url,
            ], 502);
        }
    }

    #[OA\Post(
        path: '/v1/payments/aps/sdk-token',
        tags: ['Payment'],
        summary: 'Generate SDK token for APS mobile SDK integration',
        description: 'Generates a signed SDK token that the mobile app uses to initialize the APS payment SDK. The SDK handles card input UI natively and returns a token for completing the payment.',
        security: [['AppKey' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['session_id', 'device_id'],
            properties: [
                new OA\Property(property: 'session_id', type: 'string', format: 'uuid', description: 'Checkout session UUID'),
                new OA\Property(property: 'device_id', type: 'string', description: 'Unique device identifier from the mobile app'),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'SDK token generated successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'ok', type: 'boolean', example: true),
                new OA\Property(property: 'sdk_token', type: 'string', description: 'Token to initialize APS mobile SDK'),
                new OA\Property(property: 'merchant_identifier', type: 'string'),
                new OA\Property(property: 'access_code', type: 'string'),
            ]
        )
    )]
    #[OA\Response(response: 404, description: 'Session not found')]
    #[OA\Response(response: 409, description: 'Session not in valid state')]
    #[OA\Response(response: 502, description: 'APS API error')]
    public function sdkToken(Request $request): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');
        if (! $tenant) {
            return response()->json(['ok' => false, 'error' => 'Tenant not found'], 404);
        }

        $validated = $request->validate([
            'session_id' => 'required|string',
            'device_id' => 'required|string',
        ]);

        $session = $this->sessionService->findByUuid($tenant, $validated['session_id']);
        if (! $session) {
            return response()->json(['ok' => false, 'error' => 'Session not found'], 404);
        }

        if (! $session->isInState([CheckoutSession::STATUS_PENDING, CheckoutSession::STATUS_PAYMENT_INITIATED])) {
            return response()->json([
                'ok' => false,
                'error' => 'Session is not in valid state for SDK token generation',
                'current_status' => $session->status,
            ], 409);
        }

        $gateway = $tenant->paymentGateways()
            ->where('gateway_type', 'amazon_payment_services')
            ->where('is_enabled', true)
            ->first();

        if (! $gateway) {
            return response()->json([
                'ok' => false,
                'error' => 'Amazon Payment Services not configured for this tenant',
                'fallback_checkout_url' => $session->fallback_url,
            ], 400);
        }

        try {
            $result = $this->apsService->generateSdkToken($session, $gateway, $validated['device_id']);

            // Transition to payment_initiated if still pending
            if ($session->isInState(CheckoutSession::STATUS_PENDING)) {
                $this->sessionService->transition($session, CheckoutSession::STATUS_PAYMENT_INITIATED);
                $session->update(['psp_type' => 'amazon_payment_services']);
            }

            return response()->json([
                'ok' => true,
                'sdk_token' => $result['sdk_token'],
                'merchant_identifier' => $result['merchant_identifier'],
                'access_code' => $result['access_code'],
            ]);
        } catch (PspApiException $e) {
            Log::error('APS SDK token generation failed', [
                'session_uuid' => $session->session_uuid,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'ok' => false,
                'error' => 'Failed to generate SDK token',
                'fallback_checkout_url' => $session->fallback_url,
            ], 502);
        }
    }

    #[OA\Post(
        path: '/v1/payments/aps/charge',
        tags: ['Payment'],
        summary: 'Create a charge with Amazon Payment Services using token',
        security: [['AppKey' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['session_id', 'token_name'],
            properties: [
                new OA\Property(property: 'session_id', type: 'string', format: 'uuid'),
                new OA\Property(property: 'token_name', type: 'string'),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'APS charge created')]
    #[OA\Response(response: 502, description: 'APS API error')]
    public function charge(Request $request): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');
        if (! $tenant) {
            return response()->json(['ok' => false, 'error' => 'Tenant not found'], 404);
        }

        $validated = $request->validate([
            'session_id' => 'required|string',
            'token_name' => 'required|string',
        ]);

        $session = $this->sessionService->findByUuid($tenant, $validated['session_id']);
        if (! $session) {
            return response()->json(['ok' => false, 'error' => 'Session not found'], 404);
        }

        $gateway = $tenant->paymentGateways()
            ->where('gateway_type', 'amazon_payment_services')
            ->where('is_enabled', true)
            ->first();

        if (! $gateway) {
            return response()->json([
                'ok' => false,
                'error' => 'Amazon Payment Services not configured for this tenant',
            ], 400);
        }

        try {
            $result = $this->apsService->createCharge($session, $gateway, $validated['token_name']);

            // Update transaction with fort_id
            $transaction = PaymentTransaction::where('psp_type', 'amazon_payment_services')
                ->where('psp_transaction_id', 'ORD-' . $session->session_uuid)
                ->first();

            if ($transaction) {
                $transaction->update([
                    'psp_transaction_id' => $result['fort_id'],
                    'psp_session_data' => array_merge(
                        $transaction->psp_session_data ?? [],
                        $result['psp_session_data']
                    ),
                ]);
            }

            return response()->json([
                'ok' => true,
                'fort_id' => $result['fort_id'],
                'status' => $result['status'],
                'response_code' => $result['response_code'],
                '3ds_url' => $result['3ds_url'],
                'psp_type' => 'amazon_payment_services',
            ]);
        } catch (PspApiException $e) {
            Log::error('APS charge failed', [
                'session_uuid' => $session->session_uuid,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'ok' => false,
                'error' => 'Amazon Payment Services charge failed',
            ], 502);
        }
    }

    #[OA\Post(
        path: '/v1/payments/aps/callback',
        tags: ['Payment'],
        summary: 'Amazon Payment Services payment callback/webhook'
    )]
    #[OA\Response(response: 200, description: 'Callback processed')]
    #[OA\Response(response: 401, description: 'Invalid signature')]
    public function callback(Request $request): JsonResponse
    {
        $payload = $request->all();
        $sessionUuid = $request->get('session');

        if (! $sessionUuid) {
            Log::warning('APS callback: missing session UUID');
            return response()->json(['ok' => false, 'error' => 'Missing session UUID'], 400);
        }

        $session = CheckoutSession::where('session_uuid', $sessionUuid)->first();
        if (! $session) {
            Log::warning('APS callback: session not found', ['session_uuid' => $sessionUuid]);
            return response()->json(['ok' => false, 'error' => 'Session not found'], 404);
        }

        $gateway = $session->tenant->paymentGateways()
            ->where('gateway_type', 'amazon_payment_services')
            ->where('is_enabled', true)
            ->first();

        if (! $gateway) {
            Log::warning('APS callback: gateway not found for tenant', ['tenant_id' => $session->tenant_id]);
            return response()->json(['ok' => false, 'error' => 'Gateway not found'], 404);
        }

        // Verify signature
        if (! $this->apsService->verifyResponseSignature($payload, $gateway)) {
            Log::warning('APS callback: invalid signature', ['session_uuid' => $sessionUuid]);
            return response()->json(['ok' => false, 'error' => 'Invalid signature'], 401);
        }

        $transaction = PaymentTransaction::where('checkout_session_id', $session->id)
            ->where('psp_type', 'amazon_payment_services')
            ->first();

        if (! $transaction) {
            Log::warning('APS callback: transaction not found', ['session_uuid' => $sessionUuid]);
            return response()->json(['ok' => false, 'error' => 'Transaction not found'], 404);
        }

        // Idempotent: skip if already terminal
        if ($transaction->isTerminal()) {
            return response()->json(['ok' => true]);
        }

        $responseCode = $payload['response_code'] ?? '';
        $status = strtolower($payload['status'] ?? '');
        $fortId = $payload['fort_id'] ?? '';

        // Update transaction with final data
        $transaction->update([
            'psp_transaction_id' => $fortId,
            'psp_session_data' => array_merge($transaction->psp_session_data ?? [], $payload),
        ]);

        if ($responseCode === '14000') { // Success
            if (in_array($status, ['02', 'authorized'])) {
                $transaction->update([
                    'status' => PaymentTransaction::STATUS_AUTHORIZED,
                    'authorized_at' => now(),
                ]);
                try {
                    $this->sessionService->transition($session, CheckoutSession::STATUS_AWAITING_CONFIRMATION);
                    $this->sessionService->transition($session->fresh(), CheckoutSession::STATUS_AUTHORIZED);
                } catch (\Exception $e) {
                    Log::warning('APS callback: state transition failed', ['error' => $e->getMessage()]);
                }
            } elseif (in_array($status, ['04', 'captured', 'purchase'])) {
                $transaction->update([
                    'status' => PaymentTransaction::STATUS_CAPTURED,
                    'captured_at' => now(),
                ]);
                $this->sessionService->transition($session, CheckoutSession::STATUS_COMPLETED);
            }
        } else {
            // Failed payment
            $transaction->update([
                'status' => PaymentTransaction::STATUS_FAILED,
                'failure_reason' => "APS response: {$responseCode} - " . ($payload['response_message'] ?? 'Unknown error'),
            ]);
            $this->sessionService->fail($session, "APS payment failed: {$responseCode}");
        }

        return response()->json(['ok' => true]);
    }

    #[OA\Post(
        path: '/v1/payments/aps/verify/{fort_id}',
        tags: ['Payment'],
        summary: 'Verify Amazon Payment Services payment status',
        security: [['AppKey' => []]]
    )]
    #[OA\Response(response: 200, description: 'Payment status verified')]
    #[OA\Response(response: 404, description: 'Transaction not found')]
    public function verify(Request $request, string $fortId): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');
        if (! $tenant) {
            return response()->json(['ok' => false, 'error' => 'Tenant not found'], 404);
        }

        $transaction = PaymentTransaction::where('psp_type', 'amazon_payment_services')
            ->where('psp_transaction_id', $fortId)
            ->first();

        if (! $transaction) {
            return response()->json(['ok' => false, 'error' => 'Transaction not found'], 404);
        }

        $gateway = $tenant->paymentGateways()
            ->where('gateway_type', 'amazon_payment_services')
            ->where('is_enabled', true)
            ->first();

        if (! $gateway) {
            return response()->json(['ok' => false, 'error' => 'Gateway not configured'], 400);
        }

        try {
            $status = $this->apsService->verifyPayment($fortId, $gateway);

            return response()->json([
                'ok' => true,
                'status' => $status,
                'fort_id' => $fortId,
            ]);
        } catch (PspApiException $e) {
            Log::error('APS verification failed', [
                'fort_id' => $fortId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'ok' => false,
                'error' => 'Verification failed',
            ], 502);
        }
    }

    #[OA\Post(
        path: '/v1/payments/aps/apple-pay',
        tags: ['Payment'],
        summary: 'Process Apple Pay payment via Amazon Payment Services',
        security: [['AppKey' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['session_id', 'apple_pay_data'],
            properties: [
                new OA\Property(property: 'session_id', type: 'string', format: 'uuid'),
                new OA\Property(property: 'apple_pay_data', type: 'object'),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Apple Pay charge processed')]
    #[OA\Response(response: 502, description: 'APS API error')]
    public function applePay(Request $request): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');
        if (! $tenant) {
            return response()->json(['ok' => false, 'error' => 'Tenant not found'], 404);
        }

        $validated = $request->validate([
            'session_id' => 'required|string',
            'apple_pay_data' => 'required|array',
        ]);

        $session = $this->sessionService->findByUuid($tenant, $validated['session_id']);
        if (! $session) {
            return response()->json(['ok' => false, 'error' => 'Session not found'], 404);
        }

        $gateway = $tenant->paymentGateways()
            ->where('gateway_type', 'amazon_payment_services')
            ->where('is_enabled', true)
            ->where('apple_pay_enabled', true)
            ->first();

        if (! $gateway) {
            return response()->json([
                'ok' => false,
                'error' => 'APS Apple Pay not configured for this tenant',
            ], 400);
        }

        try {
            $result = $this->apsService->initializeApplePayPayment($session, $gateway, $validated['apple_pay_data']);

            $transaction = PaymentTransaction::create([
                'tenant_id' => $tenant->id,
                'checkout_session_id' => $session->id,
                'psp_type' => 'amazon_payment_services',
                'psp_transaction_id' => $result['fort_id'],
                'psp_session_data' => $result['psp_session_data'],
                'status' => PaymentTransaction::STATUS_PENDING,
                'amount' => $session->amount,
                'currency' => $session->currency,
                'idempotency_key' => Str::uuid()->toString(),
            ]);

            $this->sessionService->transition($session, CheckoutSession::STATUS_PAYMENT_INITIATED);
            $session->update(['psp_type' => 'amazon_payment_services']);

            // Check if payment was already captured
            if (in_array($result['status'], ['04', 'captured', 'purchase'])) {
                $transaction->update([
                    'status' => PaymentTransaction::STATUS_CAPTURED,
                    'captured_at' => now(),
                ]);
                $this->sessionService->transition($session, CheckoutSession::STATUS_COMPLETED);
            } elseif (in_array($result['status'], ['02', 'authorized'])) {
                $transaction->update([
                    'status' => PaymentTransaction::STATUS_AUTHORIZED,
                    'authorized_at' => now(),
                ]);
                $this->sessionService->transition($session, CheckoutSession::STATUS_AWAITING_CONFIRMATION);
                $this->sessionService->transition($session->fresh(), CheckoutSession::STATUS_AUTHORIZED);
            }

            return response()->json([
                'ok' => true,
                'fort_id' => $result['fort_id'],
                'status' => $result['status'],
                'psp_type' => 'amazon_payment_services',
            ]);
        } catch (PspApiException $e) {
            Log::error('APS Apple Pay failed', [
                'session_uuid' => $session->session_uuid,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'ok' => false,
                'error' => 'Apple Pay payment failed',
            ], 502);
        }
    }

    #[OA\Post(
        path: '/v1/payments/aps/apple-pay/session',
        tags: ['Payment'],
        summary: 'Create Apple Pay merchant session for domain validation',
        security: [['AppKey' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['validation_url'],
            properties: [
                new OA\Property(property: 'validation_url', type: 'string'),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Apple Pay session created')]
    public function applePaySession(Request $request): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');
        if (! $tenant) {
            return response()->json(['ok' => false, 'error' => 'Tenant not found'], 404);
        }

        $validated = $request->validate([
            'validation_url' => 'required|url',
        ]);

        $gateway = $tenant->paymentGateways()
            ->where('gateway_type', 'amazon_payment_services')
            ->where('is_enabled', true)
            ->where('apple_pay_enabled', true)
            ->first();

        if (! $gateway) {
            return response()->json([
                'ok' => false,
                'error' => 'APS Apple Pay not configured for this tenant',
            ], 400);
        }

        try {
            $sessionData = $this->apsService->createApplePaySession($gateway, $validated['validation_url']);

            return response()->json([
                'ok' => true,
                'session' => $sessionData,
            ]);
        } catch (PspApiException $e) {
            Log::error('Apple Pay session creation failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'ok' => false,
                'error' => 'Apple Pay session creation failed',
            ], 502);
        }
    }
}

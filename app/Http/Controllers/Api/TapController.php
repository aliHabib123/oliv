<?php

namespace App\Http\Controllers\Api;

use App\Enums\PaymentGatewayType;
use App\Exceptions\PspApiException;
use App\Http\Controllers\Controller;
use App\Models\CheckoutSession;
use App\Models\PaymentGateway;
use App\Models\PaymentTransaction;
use App\Services\Checkout\CheckoutSessionService;
use App\Services\Payment\TapService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

class TapController extends Controller
{
    public function __construct(
        private TapService $tapService,
        private CheckoutSessionService $sessionService,
    ) {}

    #[OA\Post(
        path: '/v1/payments/tap/hash',
        tags: ['Payment'],
        summary: 'Generate HMAC-SHA256 hash for Tap SDK authentication',
        security: [['AppKey' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['amount', 'currency'],
            properties: [
                new OA\Property(property: 'amount', type: 'string', example: '118.00', description: 'Payment amount (formatted to 2 decimal places)'),
                new OA\Property(property: 'currency', type: 'string', example: 'AED', description: 'ISO currency code (3 letters)'),
                new OA\Property(property: 'transaction_reference', type: 'string', example: 'oliv_1776077904637', description: 'Unique transaction reference from the app'),
                new OA\Property(property: 'post_url', type: 'string', example: '', description: 'Webhook URL or empty string'),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Hash generated successfully')]
    #[OA\Response(response: 400, description: 'Gateway not configured')]
    public function hash(Request $request): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');
        if (! $tenant) {
            return response()->json(['ok' => false, 'error' => 'Tenant not found'], 404);
        }

        $validated = $request->validate([
            'amount' => 'required|string',
            'currency' => 'required|string|size:3',
            'transaction_reference' => 'nullable|string',
            'post_url' => 'nullable|string',
        ]);

        $gateway = $tenant->paymentGateways()
            ->where('gateway_type', 'tap')
            ->where('is_enabled', true)
            ->first();

        if (! $gateway) {
            return response()->json([
                'ok' => false,
                'error' => 'Tap payment not configured for this tenant',
            ], 400);
        }

        try {
            $hash = $this->tapService->generateHash(
                amount: $validated['amount'],
                currency: $validated['currency'],
                transactionReference: $validated['transaction_reference'] ?? '',
                postUrl: $validated['post_url'] ?? '',
                gateway: $gateway
            );

            return response()->json([
                'ok' => true,
                'hash' => $hash,
            ]);
        } catch (\Exception $e) {
            Log::error('Tap hash generation failed', [
                'transaction_reference' => $validated['transaction_reference'] ?? '',
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'ok' => false,
                'error' => 'Hash generation failed',
            ], 500);
        }
    }

    #[OA\Post(
        path: '/v1/payments/tap/initialize',
        tags: ['Payment'],
        summary: 'Initialize Tap payment for a checkout session',
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
    #[OA\Response(response: 200, description: 'Tap charge created')]
    #[OA\Response(response: 409, description: 'Session not in pending state')]
    #[OA\Response(response: 502, description: 'Tap API error')]
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
            ->where('gateway_type', 'tap')
            ->where('is_enabled', true)
            ->first();

        if (! $gateway) {
            return response()->json([
                'ok' => false,
                'error' => 'Tap payment not configured for this tenant',
                'fallback_checkout_url' => $session->fallback_url,
            ], 400);
        }

        try {
            $result = $this->tapService->initializePayment($session, $gateway);

            $transaction = PaymentTransaction::create([
                'tenant_id' => $tenant->id,
                'checkout_session_id' => $session->id,
                'psp_type' => 'tap',
                'psp_transaction_id' => $result['charge_id'],
                'psp_session_data' => $result['psp_session_data'],
                'status' => PaymentTransaction::STATUS_PENDING,
                'amount' => $session->amount,
                'currency' => $session->currency,
                'idempotency_key' => Str::uuid()->toString(),
            ]);

            $this->sessionService->transition($session, CheckoutSession::STATUS_PAYMENT_INITIATED);
            $session->update(['psp_type' => 'tap']);

            return response()->json([
                'ok' => true,
                'charge_id' => $result['charge_id'],
                'payment_url' => $result['payment_url'],
                'psp_type' => 'tap',
            ]);
        } catch (PspApiException $e) {
            Log::error('Tap initialization failed', [
                'session_uuid' => $session->session_uuid,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'ok' => false,
                'error' => 'Tap payment initialization failed',
                'fallback_checkout_url' => $session->fallback_url,
            ], 502);
        }
    }

    #[OA\Post(
        path: '/v1/payments/tap/callback',
        tags: ['Payment'],
        summary: 'Tap payment callback/webhook'
    )]
    #[OA\Response(response: 200, description: 'Callback processed')]
    public function callback(Request $request): JsonResponse
    {
        $payload = $request->all();
        $sessionUuid = $request->get('session');

        if (! $sessionUuid) {
            Log::warning('Tap callback: missing session UUID');
            return response()->json(['ok' => false, 'error' => 'Missing session UUID'], 400);
        }

        $session = CheckoutSession::where('session_uuid', $sessionUuid)->first();
        if (! $session) {
            Log::warning('Tap callback: session not found', ['session_uuid' => $sessionUuid]);
            return response()->json(['ok' => false, 'error' => 'Session not found'], 404);
        }

        $transaction = PaymentTransaction::where('checkout_session_id', $session->id)
            ->where('psp_type', 'tap')
            ->first();

        if (! $transaction) {
            Log::warning('Tap callback: transaction not found', ['session_uuid' => $sessionUuid]);
            return response()->json(['ok' => false, 'error' => 'Transaction not found'], 404);
        }

        // Idempotent: skip if already terminal
        if ($transaction->isTerminal()) {
            return response()->json(['ok' => true]);
        }

        $chargeId = $payload['id'] ?? $transaction->psp_transaction_id;
        $status = strtolower($payload['status'] ?? '');

        // Update transaction with callback data
        $transaction->update([
            'psp_session_data' => array_merge($transaction->psp_session_data ?? [], $payload),
        ]);

        if (in_array($status, ['authorized', 'approved', 'captured', 'paid'])) {
            if (in_array($status, ['authorized', 'approved'])) {
                $transaction->update([
                    'status' => PaymentTransaction::STATUS_AUTHORIZED,
                    'authorized_at' => now(),
                ]);
                try {
                    $this->sessionService->transition($session, CheckoutSession::STATUS_AWAITING_CONFIRMATION);
                    $this->sessionService->transition($session->fresh(), CheckoutSession::STATUS_AUTHORIZED);
                } catch (\Exception $e) {
                    Log::warning('Tap callback: state transition failed', ['error' => $e->getMessage()]);
                }
            } else {
                $transaction->update([
                    'status' => PaymentTransaction::STATUS_CAPTURED,
                    'captured_at' => now(),
                ]);
                $this->sessionService->transition($session, CheckoutSession::STATUS_COMPLETED);
            }
        } elseif (in_array($status, ['failed', 'cancelled', 'declined', 'voided'])) {
            $transaction->update([
                'status' => PaymentTransaction::STATUS_FAILED,
                'failure_reason' => "Tap status: {$status}",
            ]);
            $this->sessionService->fail($session, "Tap payment {$status}");
        }

        return response()->json(['ok' => true]);
    }

    #[OA\Post(
        path: '/v1/payments/tap/verify/{charge_id}',
        tags: ['Payment'],
        summary: 'Verify Tap payment status',
        security: [['AppKey' => []]]
    )]
    #[OA\Response(response: 200, description: 'Payment status verified')]
    #[OA\Response(response: 404, description: 'Transaction not found')]
    public function verify(Request $request, string $chargeId): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');
        if (! $tenant) {
            return response()->json(['ok' => false, 'error' => 'Tenant not found'], 404);
        }

        $transaction = PaymentTransaction::where('psp_type', 'tap')
            ->where('psp_transaction_id', $chargeId)
            ->first();

        if (! $transaction) {
            return response()->json(['ok' => false, 'error' => 'Transaction not found'], 404);
        }

        $gateway = $tenant->paymentGateways()
            ->where('gateway_type', 'tap')
            ->where('is_enabled', true)
            ->first();

        if (! $gateway) {
            return response()->json(['ok' => false, 'error' => 'Gateway not configured'], 400);
        }

        try {
            $status = $this->tapService->verifyPayment($chargeId, $gateway);

            // Update transaction status if changed
            if ($transaction->status !== $status) {
                $updateData = ['status' => $status];

                if ($status === PaymentTransaction::STATUS_AUTHORIZED) {
                    $updateData['authorized_at'] = now();
                } elseif ($status === PaymentTransaction::STATUS_CAPTURED) {
                    $updateData['captured_at'] = now();
                } elseif ($status === PaymentTransaction::STATUS_FAILED) {
                    $updateData['failure_reason'] = 'Verification: payment failed';
                }

                $transaction->update($updateData);

                // Update session status if needed
                $session = $transaction->checkoutSession;
                if ($session) {
                    if ($status === PaymentTransaction::STATUS_AUTHORIZED) {
                        try {
                            $this->sessionService->transition($session, CheckoutSession::STATUS_AWAITING_CONFIRMATION);
                            $this->sessionService->transition($session->fresh(), CheckoutSession::STATUS_AUTHORIZED);
                        } catch (\Exception $e) {
                            Log::warning('Tap verify: state transition failed', ['error' => $e->getMessage()]);
                        }
                    } elseif ($status === PaymentTransaction::STATUS_CAPTURED) {
                        $this->sessionService->transition($session, CheckoutSession::STATUS_COMPLETED);
                    } elseif ($status === PaymentTransaction::STATUS_FAILED) {
                        $this->sessionService->fail($session, 'Verification: payment failed');
                    }
                }
            }

            return response()->json([
                'ok' => true,
                'status' => $status,
                'charge_id' => $chargeId,
            ]);
        } catch (PspApiException $e) {
            Log::error('Tap verification failed', [
                'charge_id' => $chargeId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'ok' => false,
                'error' => 'Verification failed',
            ], 502);
        }
    }

    #[OA\Post(
        path: '/v1/payments/tap/refund',
        tags: ['Payment'],
        summary: 'Create a refund for a Tap charge',
        security: [['AppKey' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['charge_id', 'amount'],
            properties: [
                new OA\Property(property: 'charge_id', type: 'string'),
                new OA\Property(property: 'amount', type: 'number', format: 'float'),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Refund created')]
    #[OA\Response(response: 502, description: 'Tap API error')]
    public function refund(Request $request): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');
        if (! $tenant) {
            return response()->json(['ok' => false, 'error' => 'Tenant not found'], 404);
        }

        $validated = $request->validate([
            'charge_id' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
        ]);

        $gateway = $tenant->paymentGateways()
            ->where('gateway_type', 'tap')
            ->where('is_enabled', true)
            ->first();

        if (! $gateway) {
            return response()->json(['ok' => false, 'error' => 'Tap gateway not configured'], 400);
        }

        try {
            $result = $this->tapService->createRefund($validated['charge_id'], $validated['amount'], $gateway);

            return response()->json([
                'ok' => true,
                'refund_id' => $result['refund_id'],
                'status' => $result['status'],
                'psp_session_data' => $result['psp_session_data'],
            ]);
        } catch (PspApiException $e) {
            Log::error('Tap refund failed', [
                'charge_id' => $validated['charge_id'],
                'amount' => $validated['amount'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'ok' => false,
                'error' => 'Refund failed',
            ], 502);
        }
    }

    #[OA\Post(
        path: '/v1/payments/tap/apple-pay',
        tags: ['Payment'],
        summary: 'Process Apple Pay payment via Tap',
        security: [['AppKey' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['session_id', 'apple_pay_token'],
            properties: [
                new OA\Property(property: 'session_id', type: 'string', format: 'uuid'),
                new OA\Property(property: 'apple_pay_token', type: 'object'),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Apple Pay charge processed')]
    #[OA\Response(response: 502, description: 'Tap API error')]
    public function applePay(Request $request): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');
        if (! $tenant) {
            return response()->json(['ok' => false, 'error' => 'Tenant not found'], 404);
        }

        $validated = $request->validate([
            'session_id' => 'required|string',
            'apple_pay_token' => 'required|array',
        ]);

        $session = $this->sessionService->findByUuid($tenant, $validated['session_id']);
        if (! $session) {
            return response()->json(['ok' => false, 'error' => 'Session not found'], 404);
        }

        $gateway = $tenant->paymentGateways()
            ->where('gateway_type', 'tap')
            ->where('is_enabled', true)
            ->where('apple_pay_enabled', true)
            ->first();

        if (! $gateway) {
            return response()->json([
                'ok' => false,
                'error' => 'Tap Apple Pay not configured for this tenant',
            ], 400);
        }

        try {
            $result = $this->tapService->initializeApplePayPayment($session, $gateway, $validated['apple_pay_token']);

            $transaction = PaymentTransaction::create([
                'tenant_id' => $tenant->id,
                'checkout_session_id' => $session->id,
                'psp_type' => 'tap',
                'psp_transaction_id' => $result['charge_id'],
                'psp_session_data' => $result['psp_session_data'],
                'status' => PaymentTransaction::STATUS_PENDING,
                'amount' => $session->amount,
                'currency' => $session->currency,
                'idempotency_key' => Str::uuid()->toString(),
            ]);

            $this->sessionService->transition($session, CheckoutSession::STATUS_PAYMENT_INITIATED);
            $session->update(['psp_type' => 'tap']);

            // Check if payment was already captured (Apple Pay is typically instant)
            if (in_array($result['status'], ['captured', 'paid'])) {
                $transaction->update([
                    'status' => PaymentTransaction::STATUS_CAPTURED,
                    'captured_at' => now(),
                ]);
                $this->sessionService->transition($session, CheckoutSession::STATUS_COMPLETED);
            }

            return response()->json([
                'ok' => true,
                'charge_id' => $result['charge_id'],
                'status' => $result['status'],
                'psp_type' => 'tap',
            ]);
        } catch (PspApiException $e) {
            Log::error('Tap Apple Pay failed', [
                'session_uuid' => $session->session_uuid,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'ok' => false,
                'error' => 'Apple Pay payment failed',
            ], 502);
        }
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\InvalidStateTransitionException;
use App\Exceptions\PspApiException;
use App\Http\Controllers\Controller;
use App\Models\CheckoutSession;
use App\Models\PaymentTransaction;
use App\Services\Checkout\CheckoutSessionService;
use App\Services\Payment\TabbyService;
use App\Services\Payment\WebhookVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

class TabbyController extends Controller
{
    public function __construct(
        private TabbyService $tabbyService,
        private CheckoutSessionService $sessionService,
        private WebhookVerificationService $webhookVerifier,
    ) {}

    #[OA\Post(
        path: '/v1/payments/tabby/initialize',
        tags: ['Payment'],
        summary: 'Initialize Tabby payment for a checkout session',
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
    #[OA\Response(response: 200, description: 'Tabby session created')]
    #[OA\Response(response: 409, description: 'Session not in pending state')]
    #[OA\Response(response: 502, description: 'Tabby API error')]
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
            ->where('gateway_type', 'tabby')
            ->where('is_enabled', true)
            ->first();

        if (! $gateway) {
            return response()->json([
                'ok' => false,
                'error' => 'Tabby payment not configured for this tenant',
                'fallback_checkout_url' => $session->fallback_url,
            ], 400);
        }

        try {
            $result = $this->tabbyService->initializePayment($session, $gateway);

            $transaction = PaymentTransaction::create([
                'tenant_id' => $tenant->id,
                'checkout_session_id' => $session->id,
                'psp_type' => 'tabby',
                'psp_transaction_id' => $result['payment_id'],
                'psp_session_data' => $result['psp_session_data'],
                'status' => PaymentTransaction::STATUS_PENDING,
                'amount' => $session->amount,
                'currency' => $session->currency,
                'idempotency_key' => Str::uuid()->toString(),
            ]);

            $this->sessionService->transition($session, CheckoutSession::STATUS_PAYMENT_INITIATED);
            $session->update(['psp_type' => 'tabby']);

            return response()->json([
                'ok' => true,
                'payment_id' => $result['payment_id'],
                'web_url' => $result['web_url'],
                'psp_type' => 'tabby',
            ]);
        } catch (PspApiException $e) {
            Log::error('Tabby initialization failed', [
                'session_uuid' => $session->session_uuid,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'ok' => false,
                'error' => 'Tabby payment initialization failed',
                'fallback_checkout_url' => $session->fallback_url,
            ], 502);
        }
    }

    #[OA\Post(
        path: '/webhooks/tabby',
        tags: ['Webhooks'],
        summary: 'Tabby payment webhook'
    )]
    #[OA\Response(response: 200, description: 'Webhook processed')]
    #[OA\Response(response: 401, description: 'Invalid signature')]
    public function webhook(Request $request): JsonResponse
    {
        $payload = $request->all();
        $paymentId = $payload['id'] ?? $payload['payment']['id'] ?? null;

        if (! $paymentId) {
            return response()->json(['ok' => false, 'error' => 'Missing payment ID'], 400);
        }

        $transaction = PaymentTransaction::where('psp_type', 'tabby')
            ->where('psp_transaction_id', $paymentId)
            ->first();

        if (! $transaction) {
            Log::warning('Tabby webhook: transaction not found', ['payment_id' => $paymentId]);
            return response()->json(['ok' => true]);
        }

        // Verify HMAC
        $gateway = $transaction->tenant->paymentGateways()
            ->where('gateway_type', 'tabby')
            ->where('is_enabled', true)
            ->first();

        if ($gateway) {
            $secret = $gateway->getServerConfig()['secret_key'] ?? '';
            if (! $this->webhookVerifier->verifyTabbySignature($request, $secret)) {
                Log::warning('Tabby webhook: invalid signature', ['payment_id' => $paymentId]);
                return response()->json(['ok' => false, 'error' => 'Invalid signature'], 401);
            }
        }

        // Idempotent: skip if already terminal
        if ($transaction->isTerminal()) {
            return response()->json(['ok' => true]);
        }

        $status = strtolower($payload['status'] ?? '');
        $session = $transaction->checkoutSession;

        if (in_array($status, ['authorized', 'approved', 'closed'])) {
            $transaction->update([
                'status' => PaymentTransaction::STATUS_AUTHORIZED,
                'authorized_at' => now(),
            ]);
            try {
                $this->sessionService->transition($session, CheckoutSession::STATUS_AWAITING_CONFIRMATION);
                $this->sessionService->transition($session->fresh(), CheckoutSession::STATUS_AUTHORIZED);
            } catch (InvalidStateTransitionException $e) {
                Log::warning('Tabby webhook: state transition failed', ['error' => $e->getMessage()]);
            }
        } elseif (in_array($status, ['rejected', 'expired', 'error', 'canceled'])) {
            $transaction->update([
                'status' => PaymentTransaction::STATUS_FAILED,
                'failure_reason' => "Tabby status: {$status}",
            ]);
            $this->sessionService->fail($session, "Tabby payment {$status}");
        }

        return response()->json(['ok' => true]);
    }
}

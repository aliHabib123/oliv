<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\InvalidStateTransitionException;
use App\Exceptions\PspApiException;
use App\Http\Controllers\Controller;
use App\Models\CheckoutSession;
use App\Models\PaymentTransaction;
use App\Services\Checkout\CheckoutSessionService;
use App\Services\Payment\TamaraService;
use App\Services\Payment\WebhookVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

class TamaraController extends Controller
{
    public function __construct(
        private TamaraService $tamaraService,
        private CheckoutSessionService $sessionService,
        private WebhookVerificationService $webhookVerifier,
    ) {}

    #[OA\Post(
        path: '/v1/payments/tamara/initialize',
        tags: ['Payment'],
        summary: 'Initialize Tamara payment for a checkout session',
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
    #[OA\Response(response: 200, description: 'Tamara order created')]
    #[OA\Response(response: 409, description: 'Session not in pending state')]
    #[OA\Response(response: 502, description: 'Tamara API error')]
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
            ->where('gateway_type', 'tamara')
            ->where('is_enabled', true)
            ->first();

        if (! $gateway) {
            return response()->json([
                'ok' => false,
                'error' => 'Tamara payment not configured for this tenant',
                'fallback_checkout_url' => $session->fallback_url,
            ], 400);
        }

        try {
            $result = $this->tamaraService->initializePayment($session, $gateway);

            PaymentTransaction::create([
                'tenant_id' => $tenant->id,
                'checkout_session_id' => $session->id,
                'psp_type' => 'tamara',
                'psp_transaction_id' => $result['order_id'],
                'psp_session_data' => $result['psp_session_data'],
                'status' => PaymentTransaction::STATUS_PENDING,
                'amount' => $session->amount,
                'currency' => $session->currency,
                'idempotency_key' => Str::uuid()->toString(),
            ]);

            $this->sessionService->transition($session, CheckoutSession::STATUS_PAYMENT_INITIATED);
            $session->update(['psp_type' => 'tamara']);

            return response()->json([
                'ok' => true,
                'order_id' => $result['order_id'],
                'checkout_url' => $result['checkout_url'],
                'psp_type' => 'tamara',
            ]);
        } catch (PspApiException $e) {
            Log::error('Tamara initialization failed', [
                'session_uuid' => $session->session_uuid,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'ok' => false,
                'error' => 'Tamara payment initialization failed',
                'fallback_checkout_url' => $session->fallback_url,
            ], 502);
        }
    }

    #[OA\Post(
        path: '/webhooks/tamara',
        tags: ['Webhooks'],
        summary: 'Tamara payment webhook'
    )]
    #[OA\Response(response: 200, description: 'Webhook processed')]
    #[OA\Response(response: 401, description: 'Invalid signature')]
    public function webhook(Request $request): JsonResponse
    {
        $payload = $request->all();
        $orderId = $payload['order_id'] ?? null;

        if (! $orderId) {
            return response()->json(['ok' => false, 'error' => 'Missing order ID'], 400);
        }

        $transaction = PaymentTransaction::where('psp_type', 'tamara')
            ->where('psp_transaction_id', $orderId)
            ->first();

        if (! $transaction) {
            Log::warning('Tamara webhook: transaction not found', ['order_id' => $orderId]);
            return response()->json(['ok' => true]);
        }

        // Verify HMAC
        $gateway = $transaction->tenant->paymentGateways()
            ->where('gateway_type', 'tamara')
            ->where('is_enabled', true)
            ->first();

        if ($gateway) {
            $secret = $gateway->getServerConfig()['notification_webhook_id'] ?? '';
            if ($secret && ! $this->webhookVerifier->verifyTamaraSignature($request, $secret)) {
                Log::warning('Tamara webhook: invalid signature', ['order_id' => $orderId]);
                return response()->json(['ok' => false, 'error' => 'Invalid signature'], 401);
            }
        }

        // Idempotent: skip if already terminal
        if ($transaction->isTerminal()) {
            return response()->json(['ok' => true]);
        }

        $eventType = strtolower($payload['event_type'] ?? $payload['status'] ?? '');
        $session = $transaction->checkoutSession;

        if (in_array($eventType, ['approved', 'authorised', 'authorized', 'order_approved'])) {
            $transaction->update([
                'status' => PaymentTransaction::STATUS_AUTHORIZED,
                'authorized_at' => now(),
            ]);
            try {
                $this->sessionService->transition($session, CheckoutSession::STATUS_AWAITING_CONFIRMATION);
                $this->sessionService->transition($session->fresh(), CheckoutSession::STATUS_AUTHORIZED);
            } catch (InvalidStateTransitionException $e) {
                Log::warning('Tamara webhook: state transition failed', ['error' => $e->getMessage()]);
            }
        } elseif (in_array($eventType, ['declined', 'expired', 'canceled', 'order_declined'])) {
            $transaction->update([
                'status' => PaymentTransaction::STATUS_FAILED,
                'failure_reason' => "Tamara event: {$eventType}",
            ]);
            $this->sessionService->fail($session, "Tamara payment {$eventType}");
        }

        return response()->json(['ok' => true]);
    }
}

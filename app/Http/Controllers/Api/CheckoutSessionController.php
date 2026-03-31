<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CheckoutSession;
use App\Models\PaymentTransaction;
use App\Models\ShopifyOrderMapping;
use App\Services\Checkout\CheckoutSessionService;
use App\Services\Payment\TabbyService;
use App\Services\Payment\TamaraService;
use App\Services\Shopify\ShopifyAdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

class CheckoutSessionController extends Controller
{
    public function __construct(
        private CheckoutSessionService $sessionService,
        private ShopifyAdminService $shopifyAdmin,
        private TabbyService $tabbyService,
        private TamaraService $tamaraService,
    ) {}

    #[OA\Post(
        path: '/v1/checkout/session',
        tags: ['Checkout'],
        summary: 'Create a checkout session',
        security: [['AppKey' => []]]
    )]
    #[OA\HeaderParameter(name: 'Idempotency-Key', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['line_items', 'customer', 'shipping_address', 'amount', 'currency'],
            properties: [
                new OA\Property(property: 'line_items', type: 'array', items: new OA\Items(type: 'object')),
                new OA\Property(property: 'customer', type: 'object'),
                new OA\Property(property: 'shipping_address', type: 'object'),
                new OA\Property(property: 'amount', type: 'string'),
                new OA\Property(property: 'currency', type: 'string'),
            ]
        )
    )]
    #[OA\Response(response: 201, description: 'Session created')]
    #[OA\Response(response: 409, description: 'Duplicate idempotency key')]
    #[OA\Response(response: 422, description: 'Validation error')]
    public function create(Request $request): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');
        if (! $tenant) {
            return response()->json(['ok' => false, 'error' => 'Tenant not found'], 404);
        }

        $validated = $request->validate([
            'line_items' => 'required|array|min:1',
            'line_items.*.variant_id' => 'required|string',
            'line_items.*.quantity' => 'required|integer|min:1',
            'customer.email' => 'required|email',
            'customer.phone' => 'required|string',
            'shipping_address.first_name' => 'required|string',
            'shipping_address.last_name' => 'required|string',
            'shipping_address.address1' => 'required|string',
            'shipping_address.city' => 'required|string',
            'shipping_address.country_code' => 'required|string|size:2',
            'shipping_address.zip' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string|size:3',
        ]);

        $idempotencyKey = $request->header('Idempotency-Key');
        if (! $idempotencyKey) {
            return response()->json(['ok' => false, 'error' => 'Idempotency-Key header is required'], 422);
        }

        $session = $this->sessionService->createSession(
            tenant: $tenant,
            lineItems: $validated['line_items'],
            customer: $validated['customer'],
            shippingAddress: $validated['shipping_address'],
            idempotencyKey: $idempotencyKey,
            currency: $validated['currency'],
            amount: $validated['amount'],
        );

        return response()->json([
            'ok' => true,
            'session_id' => $session->session_uuid,
            'amount' => $session->amount,
            'currency' => $session->currency,
            'expires_at' => $session->expires_at?->toIso8601String(),
        ], 201);
    }

    #[OA\Post(
        path: '/v1/checkout/confirm',
        tags: ['Checkout'],
        summary: 'Confirm checkout and create Shopify order',
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
    #[OA\Response(response: 200, description: 'Order created')]
    #[OA\Response(response: 402, description: 'Payment not authorized')]
    #[OA\Response(response: 409, description: 'Already confirmed')]
    public function confirm(Request $request): JsonResponse
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

        // Already confirmed — return existing order (idempotent)
        if ($session->isInState(CheckoutSession::STATUS_ORDER_CREATED)) {
            $mapping = $session->shopifyOrderMapping;
            return response()->json([
                'ok' => true,
                'shopify_order_id' => $mapping?->shopify_order_id,
                'shopify_order_name' => $mapping?->shopify_order_name,
                'status' => 'order_created',
            ]);
        }

        // Must be in authorized or captured state
        if (! $session->isInState(CheckoutSession::STATUS_AUTHORIZED) &&
            ! $session->isInState(CheckoutSession::STATUS_CAPTURED)) {

            // Try to verify with PSP first
            $transaction = $session->paymentTransactions()->latest()->first();
            if ($transaction && ! $transaction->isTerminal()) {
                $pspStatus = $this->verifyWithPsp($session, $transaction);
                if ($pspStatus === 'authorized') {
                    $transaction->update(['status' => PaymentTransaction::STATUS_AUTHORIZED, 'authorized_at' => now()]);
                    $this->sessionService->transition($session, CheckoutSession::STATUS_AUTHORIZED);
                    $session->refresh();
                } elseif ($pspStatus === 'captured') {
                    $transaction->update(['status' => PaymentTransaction::STATUS_CAPTURED, 'captured_at' => now()]);
                    $this->sessionService->transition($session, CheckoutSession::STATUS_AUTHORIZED);
                    $this->sessionService->transition($session->fresh(), CheckoutSession::STATUS_CAPTURED);
                    $session->refresh();
                } else {
                    return response()->json(['ok' => false, 'error' => 'Payment not authorized', 'psp_status' => $pspStatus], 402);
                }
            } else {
                return response()->json(['ok' => false, 'error' => 'Payment not authorized'], 402);
            }
        }

        // Create Shopify order
        try {
            $transaction = $session->paymentTransactions()->latest()->first();

            $orderResult = $this->shopifyAdmin->createOrder($session);
            $this->shopifyAdmin->markOrderAsPaid($orderResult['order_id'], $transaction);

            ShopifyOrderMapping::create([
                'tenant_id' => $tenant->id,
                'checkout_session_id' => $session->id,
                'payment_transaction_id' => $transaction->id,
                'shopify_order_id' => $orderResult['order_id'],
                'shopify_order_name' => $orderResult['order_name'],
                'shopify_order_data' => $orderResult['order_data'],
            ]);

            $this->sessionService->transition($session->fresh(), CheckoutSession::STATUS_CAPTURED);
            $this->sessionService->transition($session->fresh(), CheckoutSession::STATUS_ORDER_CREATED);

            return response()->json([
                'ok' => true,
                'shopify_order_id' => $orderResult['order_id'],
                'shopify_order_name' => $orderResult['order_name'],
                'status' => 'order_created',
            ]);
        } catch (\Throwable $e) {
            Log::error('Shopify order creation failed', [
                'session_uuid' => $session->session_uuid,
                'error' => $e->getMessage(),
            ]);

            // Dispatch retry job
            \App\Jobs\CreateShopifyOrderJob::dispatch($session->id)->delay(now()->addSeconds(10));

            return response()->json([
                'ok' => true,
                'status' => 'processing',
                'message' => 'Order creation queued for retry',
            ], 202);
        }
    }

    private function verifyWithPsp(CheckoutSession $session, PaymentTransaction $transaction): string
    {
        $gateway = $session->tenant->paymentGateways()
            ->where('gateway_type', $session->psp_type)
            ->where('is_enabled', true)
            ->first();

        if (! $gateway) {
            return 'failed';
        }

        return match ($session->psp_type) {
            'tabby' => $this->tabbyService->verifyPayment($transaction->psp_transaction_id, $gateway),
            'tamara' => $this->tamaraService->verifyPayment($transaction->psp_transaction_id, $gateway),
            default => 'failed',
        };
    }
}

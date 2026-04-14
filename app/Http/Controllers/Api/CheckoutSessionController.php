<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CheckoutSession;
use App\Models\PaymentTransaction;
use App\Models\ShopifyOrderMapping;
use App\Services\Checkout\CheckoutSessionService;
use App\Services\Payment\TabbyService;
use App\Services\Payment\TamaraService;
use App\Services\Payment\TapService;
use App\Services\Payment\AmazonPaymentService;
use Illuminate\Support\Str;
use App\Services\Shopify\ShopifyAdminService;
use App\Services\Shopify\ShopifyStorefrontService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

class CheckoutSessionController extends Controller
{
    public function __construct(
        private CheckoutSessionService $sessionService,
        private ShopifyAdminService $shopifyAdmin,
        private ShopifyStorefrontService $shopifyStorefront,
        private TabbyService $tabbyService,
        private TamaraService $tamaraService,
        private TapService $tapService,
        private AmazonPaymentService $apsService,
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
            required: ['customer', 'shipping_address', 'amount', 'currency'],
            properties: [
                new OA\Property(property: 'line_items', type: 'array', items: new OA\Items(type: 'object'), nullable: true, description: 'Line items (optional if cart_id provided)'),
                new OA\Property(property: 'customer', type: 'object'),
                new OA\Property(property: 'shipping_address', type: 'object'),
                new OA\Property(property: 'amount', type: 'string'),
                new OA\Property(property: 'currency', type: 'string'),
                new OA\Property(property: 'cart_id', type: 'string', nullable: true, description: 'Shopify cart ID - if provided without line_items, items are fetched from cart. Cart is cleared after successful payment.'),
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
            'line_items' => 'nullable|array|min:1',
            'line_items.*.variant_id' => 'required_with:line_items|string',
            'line_items.*.quantity' => 'required_with:line_items|integer|min:1',
            'customer.email' => 'required|email',
            'customer.phone' => 'required|string',
            'shipping_address.first_name' => 'required|string',
            'shipping_address.last_name' => 'required|string',
            'shipping_address.address1' => 'required|string',
            'shipping_address.address2' => 'nullable|string',
            'shipping_address.city' => 'required|string',
            'shipping_address.province_code' => 'nullable|string',
            'shipping_address.country_code' => 'required|string|size:2',
            'shipping_address.zip' => 'required|string',
            'shipping_address.phone' => 'nullable|string',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string|size:3',
            'cart_id' => 'nullable|string',
        ]);

        // Either line_items or cart_id must be provided
        if (empty($validated['line_items']) && empty($validated['cart_id'])) {
            return response()->json(['ok' => false, 'error' => 'Either line_items or cart_id is required'], 422);
        }

        $idempotencyKey = $request->header('Idempotency-Key');
        if (! $idempotencyKey) {
            return response()->json(['ok' => false, 'error' => 'Idempotency-Key header is required'], 422);
        }

        // If cart_id provided but no line_items, fetch from cart
        $lineItems = $validated['line_items'] ?? [];
        if (empty($lineItems) && !empty($validated['cart_id'])) {
            $cart = $this->shopifyStorefront->getCart($tenant, $validated['cart_id']);
            if (empty($cart) || empty($cart['lines']['edges'])) {
                return response()->json(['ok' => false, 'error' => 'Cart is empty or not found'], 422);
            }

            $lineItems = collect($cart['lines']['edges'])->map(fn($edge) => [
                'variant_id' => $edge['node']['merchandise']['id'],
                'quantity' => $edge['node']['quantity'],
            ])->toArray();
        }

        $session = $this->sessionService->createSession(
            tenant: $tenant,
            lineItems: $lineItems,
            customer: $validated['customer'],
            shippingAddress: $validated['shipping_address'],
            idempotencyKey: $idempotencyKey,
            currency: $validated['currency'],
            amount: $validated['amount'],
            shopifyCartId: $validated['cart_id'] ?? null,
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
        path: '/v1/checkout/record-payment',
        tags: ['Checkout'],
        summary: 'Record a payment transaction from mobile SDK',
        description: 'Records a payment transaction after the mobile SDK has processed the payment. This links the PSP transaction to the checkout session.',
        security: [['AppKey' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['session_id', 'psp_type', 'psp_transaction_id'],
            properties: [
                new OA\Property(property: 'session_id', type: 'string', format: 'uuid', description: 'The checkout session ID'),
                new OA\Property(property: 'psp_type', type: 'string', enum: ['tap', 'aps'], description: 'Payment service provider'),
                new OA\Property(property: 'psp_transaction_id', type: 'string', description: 'Transaction ID from PSP (charge_id for Tap, fort_id for APS)'),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Payment recorded successfully')]
    #[OA\Response(response: 404, description: 'Session not found')]
    #[OA\Response(response: 409, description: 'Session not in valid state')]
    #[OA\Response(response: 422, description: 'Validation error')]
    public function recordPayment(Request $request): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');
        if (! $tenant) {
            return response()->json(['ok' => false, 'error' => 'Tenant not found'], 404);
        }

        $validated = $request->validate([
            'session_id' => 'required|string',
            'psp_type' => 'required|string|in:tap,aps',
            'psp_transaction_id' => 'required|string',
        ]);

        $session = $this->sessionService->findByUuid($tenant, $validated['session_id']);
        if (! $session) {
            return response()->json(['ok' => false, 'error' => 'Session not found'], 404);
        }

        // Check if session is in a valid state (pending or payment_initiated)
        if (! $session->isInState(CheckoutSession::STATUS_PENDING) &&
            ! $session->isInState(CheckoutSession::STATUS_PAYMENT_INITIATED)) {
            return response()->json([
                'ok' => false,
                'error' => 'Session is not in a valid state for recording payment',
                'current_status' => $session->status,
            ], 409);
        }

        // Check if transaction already exists (idempotent)
        $existingTransaction = $session->paymentTransactions()
            ->where('psp_transaction_id', $validated['psp_transaction_id'])
            ->first();

        if ($existingTransaction) {
            return response()->json([
                'ok' => true,
                'transaction_id' => $existingTransaction->id,
                'status' => $existingTransaction->status,
                'message' => 'Transaction already recorded',
            ]);
        }

        // Create payment transaction record
        $transaction = PaymentTransaction::create([
            'tenant_id' => $tenant->id,
            'checkout_session_id' => $session->id,
            'psp_type' => $validated['psp_type'],
            'psp_transaction_id' => $validated['psp_transaction_id'],
            'status' => PaymentTransaction::STATUS_PENDING,
            'amount' => $session->amount,
            'currency' => $session->currency,
            'idempotency_key' => Str::uuid()->toString(),
        ]);

        // Update session state and psp_type
        if ($session->isInState(CheckoutSession::STATUS_PENDING)) {
            $this->sessionService->transition($session, CheckoutSession::STATUS_PAYMENT_INITIATED);
        }
        $session->update(['psp_type' => $validated['psp_type']]);

        return response()->json([
            'ok' => true,
            'transaction_id' => $transaction->id,
            'status' => $transaction->status,
        ]);
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

            Log::info('Confirm: checking transaction', [
                'session_uuid' => $session->session_uuid,
                'session_status' => $session->status,
                'psp_type' => $session->psp_type,
                'has_transaction' => (bool) $transaction,
                'transaction_id' => $transaction?->id,
                'transaction_status' => $transaction?->status,
                'is_terminal' => $transaction ? $transaction->isTerminal() : null,
            ]);

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

        // Clear the cart after payment authorized (before order creation)
        Log::info('Cart clearing check', [
            'session_uuid' => $session->session_uuid,
            'shopify_cart_id' => $session->shopify_cart_id,
            'has_cart_id' => !empty($session->shopify_cart_id),
        ]);

        if ($session->shopify_cart_id) {
            try {
                Log::info('Attempting to clear cart', [
                    'cart_id' => $session->shopify_cart_id,
                ]);
                $result = $this->shopifyStorefront->clearCart($tenant, $session->shopify_cart_id);
                Log::info('Cart clear result', [
                    'cart_id' => $session->shopify_cart_id,
                    'success' => $result,
                ]);
            } catch (\Throwable $e) {
                Log::warning('Failed to clear cart', [
                    'session_uuid' => $session->session_uuid,
                    'cart_id' => $session->shopify_cart_id,
                    'error' => $e->getMessage(),
                ]);
            }
        } else {
            Log::info('No cart ID to clear', [
                'session_uuid' => $session->session_uuid,
            ]);
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
        // Map psp_type to gateway_type
        $gatewayType = match ($session->psp_type) {
            'aps' => 'amazon_payment_services',
            default => $session->psp_type,
        };

        $gateway = $session->tenant->paymentGateways()
            ->where('gateway_type', $gatewayType)
            ->where('is_enabled', true)
            ->first();

        Log::info('verifyWithPsp: gateway lookup', [
            'session_uuid' => $session->session_uuid,
            'psp_type' => $session->psp_type,
            'gateway_type' => $gatewayType,
            'gateway_found' => (bool) $gateway,
        ]);

        if (! $gateway) {
            return 'failed';
        }

        return match ($session->psp_type) {
            'tabby' => $this->tabbyService->verifyPayment($transaction->psp_transaction_id, $gateway),
            'tamara' => $this->tamaraService->verifyPayment($transaction->psp_transaction_id, $gateway),
            'tap' => $this->tapService->verifyPayment($transaction->psp_transaction_id, $gateway),
            'aps' => $this->apsService->verifyPayment($transaction->psp_transaction_id, $gateway),
            default => 'failed',
        };
    }
}

<?php

namespace App\Jobs;

use App\Models\CheckoutSession;
use App\Models\ShopifyOrderMapping;
use App\Services\Checkout\CheckoutSessionService;
use App\Services\Shopify\ShopifyAdminService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CreateShopifyOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public array $backoff = [10, 30, 60, 120, 300];

    public function __construct(
        private int $checkoutSessionId,
    ) {}

    public function handle(ShopifyAdminService $shopifyAdmin, CheckoutSessionService $sessionService): void
    {
        $session = CheckoutSession::find($this->checkoutSessionId);

        if (! $session) {
            Log::error('CreateShopifyOrderJob: session not found', ['id' => $this->checkoutSessionId]);
            return;
        }

        // Already has an order — skip
        if ($session->shopifyOrderMapping) {
            return;
        }

        // Must be in authorized or captured state
        if (! $session->isInState(CheckoutSession::STATUS_AUTHORIZED) &&
            ! $session->isInState(CheckoutSession::STATUS_CAPTURED)) {
            Log::warning('CreateShopifyOrderJob: session not in authorized/captured state', [
                'session_uuid' => $session->session_uuid,
                'status' => $session->status,
            ]);
            return;
        }

        $transaction = $session->paymentTransactions()->latest()->first();
        if (! $transaction) {
            Log::error('CreateShopifyOrderJob: no payment transaction found', [
                'session_uuid' => $session->session_uuid,
            ]);
            return;
        }

        $orderResult = $shopifyAdmin->createOrder($session);
        $shopifyAdmin->markOrderAsPaid($orderResult['order_id'], $transaction);

        ShopifyOrderMapping::create([
            'tenant_id' => $session->tenant_id,
            'checkout_session_id' => $session->id,
            'payment_transaction_id' => $transaction->id,
            'shopify_order_id' => $orderResult['order_id'],
            'shopify_order_name' => $orderResult['order_name'],
            'shopify_order_data' => $orderResult['order_data'],
        ]);

        $sessionService->transition($session->fresh(), CheckoutSession::STATUS_CAPTURED);
        $sessionService->transition($session->fresh(), CheckoutSession::STATUS_ORDER_CREATED);

        Log::info('CreateShopifyOrderJob: order created', [
            'session_uuid' => $session->session_uuid,
            'shopify_order_id' => $orderResult['order_id'],
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        $session = CheckoutSession::find($this->checkoutSessionId);
        if ($session) {
            $sessionService = app(CheckoutSessionService::class);
            $sessionService->fail($session, 'Shopify order creation failed after retries: ' . $exception->getMessage());
        }

        Log::error('CreateShopifyOrderJob: permanently failed', [
            'checkout_session_id' => $this->checkoutSessionId,
            'error' => $exception->getMessage(),
        ]);
    }
}

<?php

namespace App\Services\Checkout;

use App\Exceptions\InvalidStateTransitionException;
use App\Models\CheckoutSession;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CheckoutSessionService
{
    /**
     * Transition a checkout session to a new state.
     *
     * @throws InvalidStateTransitionException
     */
    public function transition(CheckoutSession $session, string $toState): CheckoutSession
    {
        if (! $session->canTransitionTo($toState)) {
            Log::warning('Invalid state transition attempted', [
                'session_uuid' => $session->session_uuid,
                'from' => $session->status,
                'to' => $toState,
            ]);

            throw new InvalidStateTransitionException(
                currentState: $session->status,
                targetState: $toState,
                sessionUuid: $session->session_uuid,
            );
        }

        $updateData = ['status' => $toState];

        if ($toState === CheckoutSession::STATUS_FAILED) {
            $updateData['failure_reason'] = $updateData['failure_reason'] ?? null;
        }

        $session->update($updateData);

        return $session->fresh();
    }

    /**
     * Create a new checkout session with idempotency support.
     */
    public function createSession(
        Tenant $tenant,
        array $lineItems,
        array $customer,
        array $shippingAddress,
        string $idempotencyKey,
        string $currency,
        string $amount,
        ?string $shopifyCartId = null,
        ?string $fallbackUrl = null,
    ): CheckoutSession {
        // Check for existing session with same idempotency key
        $existing = CheckoutSession::where('tenant_id', $tenant->id)
            ->where('idempotency_key', $idempotencyKey)
            ->first();

        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use (
            $tenant, $lineItems, $customer, $shippingAddress,
            $idempotencyKey, $currency, $amount, $shopifyCartId, $fallbackUrl
        ) {
            // Double-check inside transaction to prevent race conditions
            $existing = CheckoutSession::where('tenant_id', $tenant->id)
                ->where('idempotency_key', $idempotencyKey)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            return CheckoutSession::create([
                'tenant_id' => $tenant->id,
                'session_uuid' => Str::uuid()->toString(),
                'idempotency_key' => $idempotencyKey,
                'status' => CheckoutSession::STATUS_PENDING,
                'amount' => $amount,
                'currency' => strtoupper($currency),
                'customer_email' => $customer['email'],
                'customer_phone' => $customer['phone'],
                'shipping_address' => $shippingAddress,
                'line_items' => $lineItems,
                'shopify_cart_id' => $shopifyCartId,
                'fallback_url' => $fallbackUrl,
                'expires_at' => now()->addMinutes(30),
            ]);
        });
    }

    /**
     * Find a session by UUID scoped to a tenant.
     */
    public function findByUuid(Tenant $tenant, string $sessionUuid): ?CheckoutSession
    {
        return CheckoutSession::where('tenant_id', $tenant->id)
            ->where('session_uuid', $sessionUuid)
            ->first();
    }

    /**
     * Transition to failed with a reason.
     */
    public function fail(CheckoutSession $session, string $reason): CheckoutSession
    {
        if (! $session->canTransitionTo(CheckoutSession::STATUS_FAILED)) {
            return $session;
        }

        $session->update([
            'status' => CheckoutSession::STATUS_FAILED,
            'failure_reason' => $reason,
        ]);

        return $session->fresh();
    }

    /**
     * Expire stale sessions that have been in payment_initiated for too long.
     */
    public function expireStale(): int
    {
        $sessions = CheckoutSession::where('status', CheckoutSession::STATUS_PAYMENT_INITIATED)
            ->where('expires_at', '<', now())
            ->get();

        $count = 0;
        foreach ($sessions as $session) {
            try {
                $this->transition($session, CheckoutSession::STATUS_EXPIRED);
                $count++;
            } catch (InvalidStateTransitionException $e) {
                Log::warning('Could not expire session', [
                    'session_uuid' => $session->session_uuid,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $count;
    }
}

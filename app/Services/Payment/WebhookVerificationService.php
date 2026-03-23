<?php

namespace App\Services\Payment;

use Illuminate\Http\Request;

class WebhookVerificationService
{
    /**
     * Verify Tabby webhook HMAC signature.
     */
    public function verifyTabbySignature(Request $request, string $webhookSecret): bool
    {
        $signature = $request->header('X-Tabby-Signature', '');
        $payload = $request->getContent();

        if (empty($signature) || empty($webhookSecret)) {
            return false;
        }

        $computed = hash_hmac('sha256', $payload, $webhookSecret);

        return hash_equals($computed, $signature);
    }

    /**
     * Verify Tamara webhook HMAC signature.
     */
    public function verifyTamaraSignature(Request $request, string $webhookSecret): bool
    {
        $signature = $request->header('X-Tamara-Signature', '');
        $payload = $request->getContent();

        if (empty($signature) || empty($webhookSecret)) {
            return false;
        }

        $computed = hash_hmac('sha256', $payload, $webhookSecret);

        return hash_equals($computed, $signature);
    }
}

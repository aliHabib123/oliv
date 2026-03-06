<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Shopify\ShopifyStorefrontService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PaymentController extends Controller
{
    public function pay(Request $request)
    {
        $tenant = $request->attributes->get('tenant');
        
        if (!$tenant) {
            return response()->json(['ok' => false, 'error' => 'Tenant not found'], 404);
        }

        $validated = $request->validate([
            'payment_type' => 'required|string|in:tabby,tamara,stripe,paypal',
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'order_id' => 'required|string',
            'customer' => 'required|array',
            'customer.email' => 'required|email',
            'customer.phone' => 'required|string',
            'items' => 'required|array',
        ]);

        $paymentSettings = Cache::remember("payment_settings:{$tenant->id}", 3600, function () use ($tenant) {
            return (new ShopifyStorefrontService())->getPaymentSettings($tenant);
        });

        $paymentType = $validated['payment_type'];

        return match($paymentType) {
            'tabby' => $this->processTabby($validated, $paymentSettings),
            'tamara' => $this->processTamara($validated, $paymentSettings),
            'stripe' => $this->processStripe($validated, $paymentSettings),
            'paypal' => $this->processPaypal($validated, $paymentSettings),
        };
    }

    private function processTabby($data, $settings)
    {
        return response()->json([
            'success' => true,
            'payment_type' => 'tabby',
            'redirect_url' => 'https://checkout.tabby.ai/...',
            'session_id' => uniqid('tabby_'),
            'currency' => $settings['currencyCode'] ?? $data['currency'],
        ]);
    }

    private function processTamara($data, $settings)
    {
        return response()->json([
            'success' => true,
            'payment_type' => 'tamara',
            'redirect_url' => 'https://checkout.tamara.co/...',
            'session_id' => uniqid('tamara_'),
            'currency' => $settings['currencyCode'] ?? $data['currency'],
        ]);
    }

    private function processStripe($data, $settings)
    {
        return response()->json([
            'success' => true,
            'payment_type' => 'stripe',
            'client_secret' => 'pi_' . uniqid(),
            'accepted_cards' => $settings['acceptedCardBrands'] ?? [],
            'currency' => $settings['currencyCode'] ?? $data['currency'],
        ]);
    }

    private function processPaypal($data, $settings)
    {
        return response()->json([
            'success' => true,
            'payment_type' => 'paypal',
            'order_id' => uniqid('paypal_'),
            'currency' => $settings['currencyCode'] ?? $data['currency'],
        ]);
    }
}

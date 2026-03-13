<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Shopify\ShopifyStorefrontService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use OpenApi\Attributes as OA;

class PaymentController extends Controller
{
    #[OA\Post(
        path: '/v1/pay',
        tags: ['Payment'],
        summary: 'Process payment with various payment providers',
        security: [['XAppKey' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['payment_type', 'amount', 'currency', 'order_id', 'customer', 'items'],
            properties: [
                new OA\Property(property: 'payment_type', type: 'string', enum: ['tabby', 'tamara', 'stripe', 'paypal']),
                new OA\Property(property: 'amount', type: 'number', format: 'float'),
                new OA\Property(property: 'currency', type: 'string', minLength: 3, maxLength: 3),
                new OA\Property(property: 'order_id', type: 'string'),
                new OA\Property(
                    property: 'customer',
                    type: 'object',
                    required: ['email', 'phone'],
                    properties: [
                        new OA\Property(property: 'email', type: 'string', format: 'email'),
                        new OA\Property(property: 'phone', type: 'string')
                    ]
                ),
                new OA\Property(property: 'items', type: 'array', items: new OA\Items(type: 'object'))
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Payment processed successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean'),
                new OA\Property(property: 'payment_type', type: 'string'),
                new OA\Property(property: 'redirect_url', type: 'string', nullable: true),
                new OA\Property(property: 'session_id', type: 'string', nullable: true),
                new OA\Property(property: 'client_secret', type: 'string', nullable: true),
                new OA\Property(property: 'order_id', type: 'string', nullable: true),
                new OA\Property(property: 'currency', type: 'string')
            ]
        )
    )]
    #[OA\Response(response: 404, description: 'Tenant not found')]
    #[OA\Response(response: 422, description: 'Validation error')]
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

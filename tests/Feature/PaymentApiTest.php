<?php

namespace Tests\Feature;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PaymentApiTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Test Store',
            'shopify_shop_domain' => 'test-store.myshopify.com',
            'shopify_storefront_token' => 'test-token',
            'status' => 'active',
        ]);

        Cache::put("payment_settings:{$this->tenant->id}", [
            'currencyCode' => 'AED',
            'acceptedCardBrands' => ['VISA', 'MASTERCARD', 'AMEX'],
            'countryCode' => 'AE',
        ], 3600);
    }

    public function test_payment_endpoint_requires_app_key()
    {
        $response = $this->postJson('/api/v1/pay', [
            'payment_type' => 'tabby',
            'amount' => 100,
            'currency' => 'AED',
            'order_id' => 'ORD-123',
            'customer' => [
                'email' => 'test@example.com',
                'phone' => '+971501234567',
            ],
            'items' => [],
        ]);

        $response->assertStatus(401);
    }

    public function test_tabby_payment_success()
    {
        $response = $this->withHeaders([
            'X-App-Key' => $this->tenant->app_key,
        ])->postJson('/api/v1/pay', [
            'payment_type' => 'tabby',
            'amount' => 100.50,
            'currency' => 'AED',
            'order_id' => 'ORD-123',
            'customer' => [
                'email' => 'customer@example.com',
                'phone' => '+971501234567',
            ],
            'items' => [['id' => 1, 'qty' => 2]],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'payment_type' => 'tabby',
                'currency' => 'AED',
            ])
            ->assertJsonStructure([
                'success',
                'payment_type',
                'redirect_url',
                'session_id',
                'currency',
            ]);
    }

    public function test_tamara_payment_success()
    {
        $response = $this->withHeaders([
            'X-App-Key' => $this->tenant->app_key,
        ])->postJson('/api/v1/pay', [
            'payment_type' => 'tamara',
            'amount' => 250.00,
            'currency' => 'SAR',
            'order_id' => 'ORD-456',
            'customer' => [
                'email' => 'customer@example.com',
                'phone' => '+966501234567',
            ],
            'items' => [['id' => 2, 'qty' => 1]],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'payment_type' => 'tamara',
            ])
            ->assertJsonStructure([
                'success',
                'payment_type',
                'redirect_url',
                'session_id',
                'currency',
            ]);
    }

    public function test_stripe_payment_success()
    {
        $response = $this->withHeaders([
            'X-App-Key' => $this->tenant->app_key,
        ])->postJson('/api/v1/pay', [
            'payment_type' => 'stripe',
            'amount' => 150.00,
            'currency' => 'USD',
            'order_id' => 'ORD-789',
            'customer' => [
                'email' => 'customer@example.com',
                'phone' => '+1234567890',
            ],
            'items' => [['id' => 3, 'qty' => 3]],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'payment_type' => 'stripe',
                'accepted_cards' => ['VISA', 'MASTERCARD', 'AMEX'],
            ])
            ->assertJsonStructure([
                'success',
                'payment_type',
                'client_secret',
                'accepted_cards',
                'currency',
            ]);
    }

    public function test_paypal_payment_success()
    {
        $response = $this->withHeaders([
            'X-App-Key' => $this->tenant->app_key,
        ])->postJson('/api/v1/pay', [
            'payment_type' => 'paypal',
            'amount' => 75.00,
            'currency' => 'EUR',
            'order_id' => 'ORD-999',
            'customer' => [
                'email' => 'customer@example.com',
                'phone' => '+33123456789',
            ],
            'items' => [['id' => 4, 'qty' => 1]],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'payment_type' => 'paypal',
            ])
            ->assertJsonStructure([
                'success',
                'payment_type',
                'order_id',
                'currency',
            ]);
    }

    public function test_payment_validation_fails_with_invalid_data()
    {
        $response = $this->withHeaders([
            'X-App-Key' => $this->tenant->app_key,
        ])->postJson('/api/v1/pay', [
            'payment_type' => 'invalid_type',
            'amount' => -10,
            'currency' => 'INVALID',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['payment_type', 'amount', 'currency', 'order_id', 'customer', 'items']);
    }

    public function test_payment_requires_customer_email()
    {
        $response = $this->withHeaders([
            'X-App-Key' => $this->tenant->app_key,
        ])->postJson('/api/v1/pay', [
            'payment_type' => 'tabby',
            'amount' => 100,
            'currency' => 'AED',
            'order_id' => 'ORD-123',
            'customer' => [
                'phone' => '+971501234567',
            ],
            'items' => [],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['customer.email']);
    }
}

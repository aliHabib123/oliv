<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopifyOrderMapping extends Model
{
    protected $table = 'shopify_orders_mapping';

    protected $fillable = [
        'tenant_id',
        'checkout_session_id',
        'payment_transaction_id',
        'shopify_order_id',
        'shopify_order_name',
        'shopify_order_data',
    ];

    protected $casts = [
        'shopify_order_data' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function checkoutSession(): BelongsTo
    {
        return $this->belongsTo(CheckoutSession::class, 'checkout_session_id');
    }

    public function paymentTransaction(): BelongsTo
    {
        return $this->belongsTo(PaymentTransaction::class, 'payment_transaction_id');
    }
}

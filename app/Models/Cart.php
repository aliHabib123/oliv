<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cart extends Model
{
    protected $fillable = [
        'tenant_id',
        'shopify_customer_id',
        'device_id',
        'shopify_cart_id',
        'cart_data',
        'last_activity_at',
    ];

    protected $casts = [
        'cart_data' => 'array',
        'last_activity_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function getLineItems(): array
    {
        return $this->cart_data['lines'] ?? [];
    }

    public function getTotalQuantity(): int
    {
        $lines = $this->getLineItems();
        return array_sum(array_column($lines, 'quantity'));
    }
}

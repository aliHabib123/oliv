<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CheckoutEvent extends Model
{
    protected $fillable = [
        'tenant_id',
        'cart_id',
        'shopify_customer_id',
        'customer_email',
        'device_id',
        'checkout_id',
        'checkout_url',
        'event_type',
        'line_items',
        'subtotal_price',
        'total_price',
        'completed_at',
    ];

    protected $casts = [
        'line_items' => 'array',
        'subtotal_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'completed_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }

    public function isAbandoned(): bool
    {
        if ($this->isCompleted()) {
            return false;
        }

        // Consider abandoned if created more than 24 hours ago without completion
        return $this->created_at->diffInHours(now()) > 24;
    }
}

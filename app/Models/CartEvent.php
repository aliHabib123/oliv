<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartEvent extends Model
{
    protected $fillable = [
        'tenant_id',
        'shopify_customer_id',
        'customer_email',
        'device_id',
        'event_type',
        'product_id',
        'variant_id',
        'quantity',
        'price',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'price' => 'decimal:2',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}

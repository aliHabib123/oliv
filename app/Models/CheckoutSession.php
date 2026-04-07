<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CheckoutSession extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PAYMENT_INITIATED = 'payment_initiated';
    public const STATUS_AWAITING_CONFIRMATION = 'awaiting_confirmation';
    public const STATUS_AUTHORIZED = 'authorized';
    public const STATUS_CAPTURED = 'captured';
    public const STATUS_ORDER_CREATED = 'order_created';
    public const STATUS_FAILED = 'failed';
    public const STATUS_EXPIRED = 'expired';

    public const ALL_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_PAYMENT_INITIATED,
        self::STATUS_AWAITING_CONFIRMATION,
        self::STATUS_AUTHORIZED,
        self::STATUS_CAPTURED,
        self::STATUS_ORDER_CREATED,
        self::STATUS_FAILED,
        self::STATUS_EXPIRED,
    ];

    public const VALID_TRANSITIONS = [
        self::STATUS_PENDING => [self::STATUS_PAYMENT_INITIATED, self::STATUS_FAILED],
        self::STATUS_PAYMENT_INITIATED => [self::STATUS_AWAITING_CONFIRMATION, self::STATUS_AUTHORIZED, self::STATUS_CAPTURED, self::STATUS_FAILED, self::STATUS_EXPIRED],
        self::STATUS_AWAITING_CONFIRMATION => [self::STATUS_AUTHORIZED, self::STATUS_FAILED],
        self::STATUS_AUTHORIZED => [self::STATUS_CAPTURED, self::STATUS_FAILED],
        self::STATUS_CAPTURED => [self::STATUS_ORDER_CREATED, self::STATUS_FAILED],
        self::STATUS_ORDER_CREATED => [],
        self::STATUS_FAILED => [],
        self::STATUS_EXPIRED => [],
    ];

    protected $fillable = [
        'tenant_id',
        'session_uuid',
        'idempotency_key',
        'status',
        'psp_type',
        'amount',
        'currency',
        'customer_email',
        'customer_phone',
        'shipping_address',
        'line_items',
        'shopify_cart_id',
        'fallback_url',
        'failure_reason',
        'expires_at',
    ];

    protected $casts = [
        'shipping_address' => 'array',
        'line_items' => 'array',
        'amount' => 'decimal:2',
        'expires_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function paymentTransactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class, 'checkout_session_id');
    }

    public function shopifyOrderMapping(): HasOne
    {
        return $this->hasOne(ShopifyOrderMapping::class, 'checkout_session_id');
    }

    public function isInState(string $state): bool
    {
        return $this->status === $state;
    }

    public function canTransitionTo(string $targetState): bool
    {
        $allowed = self::VALID_TRANSITIONS[$this->status] ?? [];

        return in_array($targetState, $allowed, true);
    }

    public function markAsFailed(string $reason): void
    {
        if (! $this->canTransitionTo(self::STATUS_FAILED)) {
            return;
        }

        $this->update([
            'status' => self::STATUS_FAILED,
            'failure_reason' => $reason,
        ]);
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, [
            self::STATUS_ORDER_CREATED,
            self::STATUS_FAILED,
            self::STATUS_EXPIRED,
        ], true);
    }
}

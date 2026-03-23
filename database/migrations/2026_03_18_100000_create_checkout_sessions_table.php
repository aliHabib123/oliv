<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checkout_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants');
            $table->uuid('session_uuid')->unique();
            $table->uuid('idempotency_key');
            $table->string('status', 32)->default('pending');
            $table->string('psp_type', 32)->nullable();
            $table->decimal('amount', 12, 2);
            $table->char('currency', 3);
            $table->string('customer_email');
            $table->string('customer_phone', 32);
            $table->json('shipping_address');
            $table->json('line_items');
            $table->string('shopify_cart_id')->nullable();
            $table->text('fallback_url')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status'], 'idx_tenant_status');
            $table->index(['tenant_id', 'idempotency_key'], 'idx_idempotency');
            $table->index('expires_at', 'idx_expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkout_sessions');
    }
};

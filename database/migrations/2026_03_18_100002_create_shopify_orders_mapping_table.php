<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shopify_orders_mapping', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants');
            $table->foreignId('checkout_session_id')->unique()->constrained('checkout_sessions');
            $table->foreignId('payment_transaction_id')->constrained('payment_transactions');
            $table->string('shopify_order_id');
            $table->string('shopify_order_name', 64)->nullable();
            $table->json('shopify_order_data')->nullable();
            $table->timestamps();

            $table->index('tenant_id', 'idx_som_tenant');
            $table->index(['tenant_id', 'shopify_order_id'], 'idx_shopify_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shopify_orders_mapping');
    }
};

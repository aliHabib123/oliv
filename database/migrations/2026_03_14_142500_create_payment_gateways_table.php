<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_gateways', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('gateway_type'); // tabby, tamara, stripe, paypal, etc.
            $table->boolean('is_enabled')->default(false);
            $table->text('config')->nullable(); // encrypted JSON with all credentials
            $table->string('display_name')->nullable(); // optional custom name
            $table->integer('sort_order')->default(0); // for ordering in UI
            $table->boolean('is_test_mode')->default(true); // sandbox vs production
            $table->timestamps();

            // Unique constraint: one gateway type per tenant
            $table->unique(['tenant_id', 'gateway_type']);
            
            // Index for enabled gateways lookup
            $table->index(['tenant_id', 'is_enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_gateways');
    }
};

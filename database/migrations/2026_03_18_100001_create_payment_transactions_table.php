<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants');
            $table->foreignId('checkout_session_id')->constrained('checkout_sessions');
            $table->string('psp_type', 32);
            $table->string('psp_transaction_id')->nullable();
            $table->json('psp_session_data')->nullable();
            $table->string('status', 32)->default('pending');
            $table->decimal('amount', 12, 2);
            $table->char('currency', 3);
            $table->uuid('idempotency_key');
            $table->text('failure_reason')->nullable();
            $table->timestamp('authorized_at')->nullable();
            $table->timestamp('captured_at')->nullable();
            $table->timestamps();

            $table->index('tenant_id', 'idx_pt_tenant');
            $table->index('checkout_session_id', 'idx_pt_session');
            $table->index(['psp_type', 'psp_transaction_id'], 'idx_psp_transaction');
            $table->index(['tenant_id', 'idempotency_key'], 'idx_pt_idempotency');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};

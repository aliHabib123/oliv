<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('checkout_sessions', function (Blueprint $table) {
            $table->string('idempotency_key', 64)->change();
        });

        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->string('idempotency_key', 64)->change();
        });
    }

    public function down(): void
    {
        Schema::table('checkout_sessions', function (Blueprint $table) {
            $table->uuid('idempotency_key')->change();
        });

        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->uuid('idempotency_key')->change();
        });
    }
};

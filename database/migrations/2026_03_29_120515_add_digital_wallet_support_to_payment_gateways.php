<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payment_gateways', function (Blueprint $table) {
            $table->boolean('supports_apple_pay')->default(false)->after('is_test_mode');
            $table->boolean('supports_google_pay')->default(false)->after('supports_apple_pay');
            $table->boolean('apple_pay_enabled')->default(false)->after('supports_google_pay');
            $table->boolean('google_pay_enabled')->default(false)->after('apple_pay_enabled');
            $table->json('wallet_config')->nullable()->after('google_pay_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_gateways', function (Blueprint $table) {
            $table->dropColumn([
                'supports_apple_pay',
                'supports_google_pay',
                'apple_pay_enabled',
                'google_pay_enabled',
                'wallet_config',
            ]);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add website column to tenants table
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('website')->nullable()->after('phone');
        });

        // Update subscriptions table to add pending_payment status and stripe_subscription_id
        // SQLite doesn't support MODIFY COLUMN with ENUM
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE subscriptions MODIFY COLUMN status ENUM('pending_payment', 'active', 'expired', 'cancelled') DEFAULT 'pending_payment'");
        }

        // Add stripe_subscription_id column if it doesn't exist
        if (!Schema::hasColumn('subscriptions', 'stripe_subscription_id')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->string('stripe_subscription_id')->nullable()->after('status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('website');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            if (Schema::hasColumn('subscriptions', 'stripe_subscription_id')) {
                $table->dropColumn('stripe_subscription_id');
            }
        });

        // Revert enum to original values
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE subscriptions MODIFY COLUMN status ENUM('active', 'expired', 'cancelled') DEFAULT 'active'");
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add 'past_due' status to subscriptions enum
        DB::statement("ALTER TABLE subscriptions MODIFY COLUMN status ENUM('pending_payment', 'active', 'past_due', 'expired', 'cancelled') DEFAULT 'pending_payment'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove 'past_due' status
        DB::statement("ALTER TABLE subscriptions MODIFY COLUMN status ENUM('pending_payment', 'active', 'expired', 'cancelled') DEFAULT 'pending_payment'");
    }
};

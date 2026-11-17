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
        Schema::table('tenants', function (Blueprint $table) {
            // Expiring offline tokens (required by Shopify for public apps as of Apr 2026)
            // return a refresh token (~90 days) used to refresh the ~1h admin token.
            $table->text('shopify_admin_refresh_token')->nullable()->after('shopify_admin_token_expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('shopify_admin_refresh_token');
        });
    }
};

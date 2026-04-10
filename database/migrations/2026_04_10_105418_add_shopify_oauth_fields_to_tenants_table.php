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
            $table->string('shopify_client_id')->nullable()->after('shopify_admin_token');
            $table->string('shopify_client_secret')->nullable()->after('shopify_client_id');
            $table->timestamp('shopify_admin_token_expires_at')->nullable()->after('shopify_client_secret');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['shopify_client_id', 'shopify_client_secret', 'shopify_admin_token_expires_at']);
        });
    }
};

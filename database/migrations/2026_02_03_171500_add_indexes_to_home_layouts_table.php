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
        Schema::table('home_layouts', function (Blueprint $table) {
            // Add composite index for the most common query pattern
            $table->index(['tenant_id', 'category_handle', 'status'], 'idx_tenant_category_status');

            // Add composite index for default layout lookup
            $table->index(['tenant_id', 'is_default', 'status'], 'idx_tenant_default_status');

            // Add index for category handle lookups
            $table->index('category_handle', 'idx_category_handle');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('home_layouts', function (Blueprint $table) {
            $table->dropIndex('idx_tenant_category_status');
            $table->dropIndex('idx_tenant_default_status');
            $table->dropIndex('idx_category_handle');
        });
    }
};

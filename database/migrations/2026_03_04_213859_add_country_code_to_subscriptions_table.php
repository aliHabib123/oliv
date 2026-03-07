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
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('country_code', 2)->nullable()->after('billing_period');
            $table->foreign('country_code')->references('code')->on('countries')->onDelete('set null');
            $table->index('country_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropForeign(['country_code']);
            $table->dropIndex(['country_code']);
            $table->dropColumn('country_code');
        });
    }
};

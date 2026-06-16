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
        Schema::create('onboarding_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('platform')->default('shopify');
            $table->unsignedTinyInteger('step')->default(1);
            $table->string('shopify_domain')->nullable();
            $table->json('imported_data')->nullable();
            $table->json('wizard_payload')->nullable();
            $table->json('ai_recommended')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('tenant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('onboarding_sessions');
    }
};

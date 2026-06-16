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
        Schema::create('app_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('app_name');
            $table->string('app_icon_url')->nullable();
            $table->string('splash_url')->nullable();
            $table->string('primary_color', 7)->default('#000000');
            $table->string('secondary_color', 7)->default('#ffffff');
            $table->string('font_family')->default('Inter');
            $table->unsignedBigInteger('theme_id')->nullable();
            $table->json('home_layout')->nullable();
            $table->json('navigation')->nullable();
            $table->string('publish_status')->default('draft');
            $table->string('bundle_id_ios')->nullable();
            $table->string('bundle_id_android')->nullable();
            $table->timestamps();

            $table->unique('tenant_id');
            $table->index('theme_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_configs');
    }
};

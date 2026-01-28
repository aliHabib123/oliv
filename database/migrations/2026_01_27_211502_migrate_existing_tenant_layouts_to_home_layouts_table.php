<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Tenant;
use App\Models\HomeLayout;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Migrate existing tenant.home_layout data to home_layouts table as default layouts
        $tenants = Tenant::whereNotNull('home_layout')->get();

        foreach ($tenants as $tenant) {
            // Check if this tenant already has a default layout
            $existingDefault = HomeLayout::where('tenant_id', $tenant->id)
                ->where('is_default', true)
                ->first();

            // Only migrate if no default layout exists
            if (!$existingDefault && !empty($tenant->home_layout)) {
                HomeLayout::create([
                    'tenant_id' => $tenant->id,
                    'category_handle' => null,
                    'category_gid' => null,
                    'category_title' => null,
                    'is_default' => true,
                    'layout_data' => $tenant->home_layout,
                    'name' => 'Default Layout',
                    'status' => 'active',
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove all default layouts (those without category_handle)
        HomeLayout::whereNull('category_handle')
            ->where('is_default', true)
            ->delete();
    }
};

<?php

namespace App\Services;

use App\Models\AppConfiguration;
use App\Models\HomeLayout;
use App\Models\Tenant;
use App\Services\Shopify\ShopifyStorefrontService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CategorySyncService
{
    public function __construct(
        private ShopifyStorefrontService $shopifyService
    ) {}

    /**
     * Sync categories from flutter-menu and create HomeLayout rows for each.
     *
     * @param Tenant $tenant
     * @return array{success: bool, message: string, categories_synced: int}
     */
    public function syncCategories(Tenant $tenant): array
    {
        // Check if sync is enabled
        $config = AppConfiguration::where('tenant_id', $tenant->id)->first();
        if (!$config || !($config->config['basic']['sync_level_0_categories'] ?? false)) {
            return [
                'success' => false,
                'message' => 'Category sync is not enabled for this tenant',
                'categories_synced' => 0,
            ];
        }

        try {
            // Fetch categories from flutter-menu
            $categories = $this->shopifyService->getFlutterMenuLevel0Categories($tenant);

            if (empty($categories)) {
                return [
                    'success' => false,
                    'message' => 'No level 0 categories found in flutter-menu',
                    'categories_synced' => 0,
                ];
            }

            // Load template layout
            $templatePath = storage_path('app/templates/tenant_1_home_layout.json');
            $templateLayout = [];

            if (file_exists($templatePath)) {
                $templateLayout = json_decode(file_get_contents($templatePath), true);
            }

            $syncedCount = 0;

            foreach ($categories as $category) {
                // Check if layout already exists for this category
                $existingLayout = HomeLayout::where('tenant_id', $tenant->id)
                    ->where('category_handle', $category['handle'])
                    ->first();

                if (!$existingLayout) {
                    // Create new layout for this category using template
                    HomeLayout::create([
                        'tenant_id' => $tenant->id,
                        'category_handle' => $category['handle'],
                        'category_gid' => $category['gid'],
                        'category_title' => $category['title'],
                        'is_default' => false,
                        'layout_data' => $templateLayout ?: [],
                        'name' => $category['title'] . ' Layout',
                        'status' => 'active',
                    ]);

                    $syncedCount++;
                } else {
                    // Update existing layout metadata (but keep layout_data)
                    $existingLayout->update([
                        'category_gid' => $category['gid'],
                        'category_title' => $category['title'],
                    ]);
                }
            }

            // Ensure there's a default layout if none exists
            $defaultLayout = HomeLayout::where('tenant_id', $tenant->id)
                ->where('is_default', true)
                ->first();

            if (!$defaultLayout) {
                // Use tenant's existing home_layout or template
                $defaultLayoutData = $tenant->home_layout ?: $templateLayout ?: [];

                HomeLayout::create([
                    'tenant_id' => $tenant->id,
                    'category_handle' => null,
                    'category_gid' => null,
                    'category_title' => null,
                    'is_default' => true,
                    'layout_data' => $defaultLayoutData,
                    'name' => 'Default Layout',
                    'status' => 'active',
                ]);
            }

            Log::info('Categories synced successfully', [
                'tenant_id' => $tenant->id,
                'categories_found' => count($categories),
                'new_layouts_created' => $syncedCount,
            ]);

            return [
                'success' => true,
                'message' => "Successfully synced {$syncedCount} categories from flutter-menu",
                'categories_synced' => $syncedCount,
            ];

        } catch (\Throwable $e) {
            Log::error('Category sync failed', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to sync categories: ' . $e->getMessage(),
                'categories_synced' => 0,
            ];
        }
    }
}

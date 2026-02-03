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
     * Sync categories from flutter-menu. Does not auto-create layouts.
     * User should assign layouts via the Home Layout Assignments page.
     *
     * @param Tenant $tenant
     * @return array{success: bool, message: string, categories: array}
     */
    public function syncCategories(Tenant $tenant): array
    {
        try {
            // Fetch categories from flutter-menu
            $categories = $this->shopifyService->getFlutterMenuLevel0Categories($tenant);

            if (empty($categories)) {
                return [
                    'success' => false,
                    'message' => 'No level 0 categories found in flutter-menu',
                    'categories' => [],
                ];
            }

            // Update existing layout metadata for synced categories
            foreach ($categories as $category) {
                $existingLayout = HomeLayout::where('tenant_id', $tenant->id)
                    ->where('category_handle', $category['handle'])
                    ->first();

                if ($existingLayout) {
                    // Update metadata only (keep layout_data intact)
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
                // Load template for default layout
                $templatePath = storage_path('app/templates/tenant_1_home_layout.json');
                $templateLayout = [];

                if (file_exists($templatePath)) {
                    $templateLayout = json_decode(file_get_contents($templatePath), true);
                }

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
            ]);

            return [
                'success' => true,
                'message' => "Successfully synced " . count($categories) . " categories from flutter-menu",
                'categories' => $categories,
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
                'categories' => [],
            ];
        }
    }
}

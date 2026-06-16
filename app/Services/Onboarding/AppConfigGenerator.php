<?php

namespace App\Services\Onboarding;

use App\Models\AppConfig;
use App\Models\AppTheme;
use App\Models\Category;
use App\Models\OnboardingSession;
use Illuminate\Support\Str;

class AppConfigGenerator
{
    /**
     * Generate the full app configuration from a completed onboarding session.
     */
    public function generate(OnboardingSession $session): AppConfig
    {
        $payload = $session->wizard_payload ?? [];
        $branding = $payload['branding'] ?? [];
        $themeId = $payload['theme_id'] ?? null;
        $tenant = $session->tenant;

        $appConfig = AppConfig::updateOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'app_name' => $branding['app_name'] ?? $tenant->name,
                'app_icon_url' => $branding['app_icon_url'] ?? null,
                'splash_url' => $branding['splash_url'] ?? null,
                'primary_color' => $branding['primary_color'] ?? '#000000',
                'secondary_color' => $branding['secondary_color'] ?? '#ffffff',
                'font_family' => $branding['font_family'] ?? 'Inter',
                'theme_id' => $themeId,
                'home_layout' => $this->buildHomeLayout($themeId),
                'navigation' => $this->buildNavigation(),
                'publish_status' => 'building',
                'bundle_id_ios' => $this->generateBundleId($tenant->name, 'ios'),
                'bundle_id_android' => $this->generateBundleId($tenant->name, 'android'),
            ]
        );

        // Save categories from imported data
        $this->saveCategories($session, $tenant);

        $session->update(['completed_at' => now()]);

        return $appConfig;
    }

    private function buildHomeLayout(?int $themeId): array
    {
        if (! $themeId) {
            return ['sections' => ['hero', 'categories', 'featured_products', 'new_arrivals']];
        }

        $theme = AppTheme::find($themeId);
        $config = $theme?->default_config ?? [];

        return [
            'sections' => ['hero', 'categories', 'featured_products', 'new_arrivals'],
            'grid_columns' => $config['grid_columns'] ?? 2,
            'card_style' => $config['card_style'] ?? 'flat',
            'nav_style' => $config['nav_style'] ?? 'bottom_bar',
        ];
    }

    private function buildNavigation(): array
    {
        return [
            'tabs' => [
                ['key' => 'home', 'label' => 'Home', 'icon' => 'home'],
                ['key' => 'categories', 'label' => 'Shop', 'icon' => 'grid'],
                ['key' => 'cart', 'label' => 'Cart', 'icon' => 'shopping-bag'],
                ['key' => 'account', 'label' => 'Account', 'icon' => 'user'],
            ],
        ];
    }

    private function generateBundleId(string $storeName, string $platform): string
    {
        $slug = Str::slug($storeName, '');

        return 'com.oliv.' . Str::limit($slug, 30, '');
    }

    private function saveCategories(OnboardingSession $session, $tenant): void
    {
        $importedData = $session->imported_data ?? [];
        $categories = $importedData['catalog']['categories'] ?? [];

        if (empty($categories)) {
            return;
        }

        foreach ($categories as $index => $category) {
            Category::updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'handle' => $category['handle'] ?? Str::slug($category['title']),
                ],
                [
                    'title' => $category['title'],
                    'image_url' => $category['image'] ?? null,
                    'sort_order' => $index,
                    'is_active' => true,
                ]
            );
        }
    }
}

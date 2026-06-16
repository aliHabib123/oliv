<?php

namespace Database\Seeders;

use App\Models\AppTheme;
use Illuminate\Database\Seeder;

class AppThemeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $themes = [
            [
                'name' => 'Minimal',
                'default_config' => json_encode([
                    'nav_style' => 'bottom_bar',
                    'card_style' => 'flat',
                    'grid_columns' => 2,
                ]),
            ],
            [
                'name' => 'Bold',
                'default_config' => json_encode([
                    'nav_style' => 'bottom_bar',
                    'card_style' => 'elevated',
                    'grid_columns' => 1,
                ]),
            ],
            [
                'name' => 'Classic',
                'default_config' => json_encode([
                    'nav_style' => 'drawer',
                    'card_style' => 'bordered',
                    'grid_columns' => 2,
                ]),
            ],
            [
                'name' => 'Modern',
                'default_config' => json_encode([
                    'nav_style' => 'bottom_bar',
                    'card_style' => 'rounded',
                    'grid_columns' => 2,
                ]),
            ],
            [
                'name' => 'Luxe',
                'default_config' => json_encode([
                    'nav_style' => 'bottom_bar',
                    'card_style' => 'elevated',
                    'grid_columns' => 1,
                ]),
            ],
            [
                'name' => 'Fresh',
                'default_config' => json_encode([
                    'nav_style' => 'tab_bar',
                    'card_style' => 'flat',
                    'grid_columns' => 3,
                ]),
            ],
        ];

        foreach ($themes as $theme) {
            AppTheme::updateOrCreate(
                ['name' => $theme['name']],
                $theme
            );
        }
    }
}

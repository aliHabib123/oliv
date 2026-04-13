<?php

namespace App\Filament\Resources\Templates\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                Select::make('industry')
                    ->options([
                        'fashion' => 'Fashion',
                        'jewellery' => 'Jewellery',
                        'beauty' => 'Beauty',
                        'perfume' => 'Perfume',
                        'watches_accessories' => 'Watches & Accessories',
                        'home_furniture' => 'Home & Furniture',
                        'sportswear_fitness' => 'Sportswear & Fitness',
                        'kids_baby' => 'Kids & Baby',
                        'health_wellness' => 'Health & Wellness / Supplements',
                        'modest_fashion' => 'Modest Fashion / Abayas',
                        'luggage_travel' => 'Luggage & Travel Accessories',
                        'food_grocery' => 'Food & Grocery (specialty)',
                        'pet_products' => 'Pet Products',
                        'optical_eyewear' => 'Optical / Eyewear',
                        'stationery_lifestyle' => 'Stationery & Lifestyle',
                        'electronics' => 'Electronics',
                    ])
                    ->searchable()
                    ->nullable(),

                Select::make('style')
                    ->options([
                        'modern' => 'Modern',
                        'classic' => 'Classic',
                        'minimal' => 'Minimal',
                        'bold' => 'Bold',
                        'elegant' => 'Elegant',
                        'playful' => 'Playful',
                    ])
                    ->searchable()
                    ->nullable()
                    ->hidden(),

                FileUpload::make('preview_image')
                    ->label('Preview Image')
                    ->image()
                    ->disk('public')
                    ->directory('templates/previews')
                    ->maxSize(5120)
                    ->helperText('Template preview screenshot (max 5MB)')
                    ->columnSpanFull(),

                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
            ]);
    }
}

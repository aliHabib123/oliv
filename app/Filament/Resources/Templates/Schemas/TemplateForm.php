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
                        'restaurant' => 'Restaurant',
                        'retail' => 'Retail',
                        'healthcare' => 'Healthcare',
                        'education' => 'Education',
                        'real_estate' => 'Real Estate',
                        'technology' => 'Technology',
                        'fashion' => 'Fashion',
                        'automotive' => 'Automotive',
                        'sports' => 'Sports',
                        'other' => 'Other',
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
                    ->nullable(),

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

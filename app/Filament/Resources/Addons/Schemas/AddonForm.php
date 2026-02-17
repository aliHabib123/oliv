<?php

namespace App\Filament\Resources\Addons\Schemas;

use Filament\Forms;
use Filament\Schemas\Schema;

class AddonForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('subtitle')
                    ->maxLength(255)
                    ->helperText('Short subtitle shown in addon cards')
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('key')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255)
                    ->helperText('Unique identifier for programmatic access (e.g., whatsapp_support)')
                    ->regex('/^[a-z0-9_-]+$/')
                    ->placeholder('e.g., whatsapp_support'),

                Forms\Components\Textarea::make('description')
                    ->rows(3)
                    ->helperText('Full description shown in view more modal')
                    ->columnSpanFull(),

                Forms\Components\FileUpload::make('image')
                    ->label('Image')
                    ->image()
                    ->disk('public')
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp'])
                    ->maxSize(5120)
                    ->helperText('Optional image or GIF (max 5MB)')
                    ->directory('addons/images')
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('video')
                    ->label('Video URL')
                    ->url()
                    ->maxLength(255)
                    ->helperText('Optional video URL (YouTube, Vimeo, etc.)')
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->prefix('$')
                    ->minValue(0),

                Forms\Components\Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
            ]);
    }
}

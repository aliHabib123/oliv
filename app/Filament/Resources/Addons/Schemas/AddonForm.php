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

                Forms\Components\TextInput::make('key')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255)
                    ->helperText('Unique identifier for programmatic access (e.g., whatsapp_support)')
                    ->regex('/^[a-z0-9_-]+$/')
                    ->placeholder('e.g., whatsapp_support'),

                Forms\Components\Textarea::make('description')
                    ->rows(3)
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

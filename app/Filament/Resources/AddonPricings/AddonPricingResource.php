<?php

namespace App\Filament\Resources\AddonPricings;

use App\Filament\Resources\AddonPricings\Pages\CreateAddonPricing;
use App\Filament\Resources\AddonPricings\Pages\EditAddonPricing;
use App\Filament\Resources\AddonPricings\Pages\ListAddonPricings;
use App\Filament\Resources\AddonPricings\Schemas\AddonPricingForm;
use App\Filament\Resources\AddonPricings\Tables\AddonPricingsTable;
use App\Models\AddonPricing;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AddonPricingResource extends Resource
{
    protected static ?string $model = AddonPricing::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?string $navigationLabel = 'Addon Pricing';

    protected static string|\UnitEnum|null $navigationGroup = 'Pricing';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'Addon Pricing';

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return AddonPricingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AddonPricingsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAddonPricings::route('/'),
            'create' => CreateAddonPricing::route('/create'),
            'edit' => EditAddonPricing::route('/{record}/edit'),
        ];
    }
}

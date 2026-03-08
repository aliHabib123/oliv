<?php

namespace App\Filament\Resources\PackagePricings;

use App\Filament\Resources\PackagePricings\Pages\CreatePackagePricing;
use App\Filament\Resources\PackagePricings\Pages\EditPackagePricing;
use App\Filament\Resources\PackagePricings\Pages\ListPackagePricings;
use App\Filament\Resources\PackagePricings\Schemas\PackagePricingForm;
use App\Filament\Resources\PackagePricings\Tables\PackagePricingsTable;
use App\Models\PackagePricing;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PackagePricingResource extends Resource
{
    protected static ?string $model = PackagePricing::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    protected static ?string $navigationLabel = 'Package Pricing';

    protected static string|\UnitEnum|null $navigationGroup = 'Pricing';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'Package Pricing';

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
        return PackagePricingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PackagePricingsTable::configure($table);
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
            'index' => ListPackagePricings::route('/'),
            'create' => CreatePackagePricing::route('/create'),
            'edit' => EditPackagePricing::route('/{record}/edit'),
        ];
    }
}

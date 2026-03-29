<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CardBrandResource\Pages;
use App\Models\CardBrand;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CardBrandResource extends Resource
{
    protected static ?string $model = CardBrand::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Card Brands';

    public static function getCardBrandOptions(): array
    {
        return [
            'visa' => 'Visa',
            'mastercard' => 'Mastercard',
            'amex' => 'American Express',
            'mada' => 'MADA',
            'apple_pay' => 'Apple Pay',
            'google_pay' => 'Google Pay',
            'discover' => 'Discover',
            'diners' => 'Diners Club',
            'jcb' => 'JCB',
            'unionpay' => 'UnionPay',
            'maestro' => 'Maestro',
            'elo' => 'Elo',
            'mir' => 'Mir',
            'cartes_bancaires' => 'Cartes Bancaires',
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\Select::make('slug')
                ->label('Card Brand')
                ->options(self::getCardBrandOptions())
                ->required()
                ->searchable()
                ->live()
                ->unique(ignoreRecord: true, modifyRuleUsing: function ($rule) {
                    $tenantId = filament()->getTenant()?->id ?? auth()->user()?->tenant_id;
                    return $rule->where('tenant_id', $tenantId);
                })
                ->afterStateUpdated(fn ($state, $set) => $set('name', self::getCardBrandOptions()[$state] ?? $state))
                ->helperText('Select the card brand'),

            Forms\Components\Hidden::make('name'),

            Forms\Components\FileUpload::make('image')
                ->label('Brand Logo')
                ->image()
                ->directory('card-brands')
                ->imageResizeMode('contain')
                ->imageCropAspectRatio('3:2')
                ->imageResizeTargetWidth('120')
                ->imageResizeTargetHeight('80')
                ->helperText('Upload the card brand logo (recommended: 120x80px, PNG with transparency)'),

            Forms\Components\Toggle::make('is_enabled')
                ->label('Enabled')
                ->default(true)
                ->helperText('Show this card brand to customers')
                ->inline(false),

            Forms\Components\TextInput::make('sort_order')
                ->label('Display Order')
                ->numeric()
                ->default(0)
                ->helperText('Lower numbers appear first'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label('Logo')
                    ->circular(false)
                    ->height(40),

                Tables\Columns\TextColumn::make('name')
                    ->label('Brand Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_enabled')
                    ->label('Enabled')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_enabled')
                    ->label('Enabled')
                    ->boolean(),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCardBrands::route('/'),
            'create' => Pages\CreateCardBrand::route('/create'),
            'edit' => Pages\EditCardBrand::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $tenantId = filament()->getTenant()?->id ?? auth()->user()?->tenant_id;
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        return $query;
    }

    public static function getNavigationBadge(): ?string
    {
        $tenantId = filament()->getTenant()?->id ?? auth()->user()?->tenant_id;
        if (!$tenantId) {
            return null;
        }

        $count = CardBrand::where('tenant_id', $tenantId)
            ->where('is_enabled', true)
            ->count();

        return $count > 0 ? (string) $count : null;
    }
}

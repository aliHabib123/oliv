<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TenantResource\Pages;
use App\Models\Tenant;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-building-storefront';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255),

            Forms\Components\TextInput::make('app_key')
                ->label('App Key (X-App-Key)')
                ->disabled()
                ->dehydrated(false)
                ->helperText('Public key used by Flutter as X-App-Key; generated automatically.')
                ->columnSpanFull(),

            Forms\Components\TextInput::make('shopify_shop_domain')
                ->label('Shopify shop domain')
                ->maxLength(255),

            Forms\Components\Textarea::make('shopify_storefront_token')
                ->label('Shopify Storefront API token')
                ->columnSpanFull(),

            Forms\Components\Select::make('status')
                ->options([
                    'active' => 'Active',
                    'suspended' => 'Suspended',
                ])
                ->required(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('app_key')
                    ->label('App Key')
                    ->copyable()
                    ->limit(20),

                Tables\Columns\TextColumn::make('shopify_shop_domain')
                    ->label('Shopify Domain')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'success' => 'active',
                        'danger' => 'suspended',
                    ]),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Actions\EditAction::make(),
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
            'index' => Pages\ListTenants::route('/'),
            'create' => Pages\CreateTenant::route('/create'),
            'edit' => Pages\EditTenant::route('/{record}/edit'),
        ];
    }

    protected static function canManageTenants(): bool
    {
        $user = auth()->user();

        return (bool) $user?->hasRole('super_admin');
    }

    public static function canViewAny(): bool
    {
        return self::canManageTenants();
    }

    public static function canCreate(): bool
    {
        return self::canManageTenants();
    }

    public static function canEdit(Model $record): bool
    {
        return self::canManageTenants();
    }

    public static function canDelete(Model $record): bool
    {
        return self::canManageTenants();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return self::canManageTenants();
    }
}

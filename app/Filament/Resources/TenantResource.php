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
use Illuminate\Database\Eloquent\Builder;
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

            Forms\Components\Textarea::make('shopify_admin_token')
                ->label('Shopify Admin API token (auto-managed)')
                ->helperText('Auto-refreshed using client credentials. Leave empty if using OAuth.')
                ->columnSpanFull()
                ->disabled(),

            Forms\Components\TextInput::make('shopify_client_id')
                ->label('Shopify App Client ID')
                ->helperText('From your Shopify app credentials')
                ->columnSpanFull(),

            Forms\Components\TextInput::make('shopify_client_secret')
                ->label('Shopify App Client Secret')
                ->helperText('From your Shopify app credentials')
                ->password()
                ->columnSpanFull(),

            Forms\Components\Select::make('status')
                ->options([
                    'active' => 'Active',
                    'suspended' => 'Suspended',
                ])
                ->required(),

            Forms\Components\Repeater::make('home_layout')
                ->label('Home layout blocks')
                ->hiddenLabel()
                ->schema([
                    Forms\Components\Hidden::make('type')
                        ->default('banner'),

                    Forms\Components\TextInput::make('title')
                        ->label('Title')
                        ->maxLength(255),

                    Forms\Components\TextInput::make('subtitle')
                        ->label('Subtitle')
                        ->maxLength(255),

                    Forms\Components\TextInput::make('image_url')
                        ->label('Image URL')
                        ->maxLength(2048),

                    Forms\Components\TextInput::make('cta_label')
                        ->label('CTA label')
                        ->maxLength(255),

                    Forms\Components\Select::make('cta_target_type')
                        ->label('CTA target type')
                        ->options([
                            'none' => 'None',
                            'product' => 'Product',
                            'brand' => 'Brand',
                            'category' => 'Category',
                            'url' => 'URL',
                        ])
                        ->default('none'),

                    Forms\Components\TextInput::make('cta_target_id')
                        ->label('CTA target ID')
                        ->maxLength(255)
                        ->visible(fn (callable $get): bool => in_array($get('cta_target_type'), ['product', 'brand', 'category'], true)),

                    Forms\Components\TextInput::make('cta_target_url')
                        ->label('CTA target URL')
                        ->maxLength(2048)
                        ->visible(fn (callable $get): bool => $get('cta_target_type') === 'url'),
                ])
                ->orderable()
                ->collapsible()
                ->defaultItems(0)
                ->columnSpanFull(),
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
                Actions\Action::make('viewConfig')
                    ->label('See Config')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->color('success')
                    ->url(fn (Tenant $record): ?string => 
                        \App\Models\AppConfig::where('tenant_id', $record->id)->exists()
                            ? route('filament.admin.resources.tenants.config', $record)
                            : null
                    )
                    ->visible(fn (Tenant $record): bool => 
                        \App\Models\AppConfig::where('tenant_id', $record->id)->exists()
                    ),
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
            'config' => Pages\ViewTenantConfig::route('/{record}/config'),
        ];
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        return $user->hasRole('super_admin');
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        // Resource-level access: super_admin + owner + admin can hit Tenant pages.
        return $user->hasAnyRole(['super_admin', 'owner', 'admin']);
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();

        return (bool) $user?->hasRole('super_admin');
    }

    public static function canEdit(Model $record): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if ($user->hasRole('super_admin')) {
            return true;
        }

        if ($user->hasAnyRole(['owner', 'admin'])) {
            return true;
        }

        return false;
    }

    public static function canDelete(Model $record): bool
    {
        $user = auth()->user();

        return (bool) $user?->hasRole('super_admin');
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        return $user->hasRole('super_admin');
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $user = auth()->user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->hasRole('super_admin')) {
            return $query;
        }

        if ($user->hasAnyRole(['owner', 'admin']) && $user->tenant_id !== null) {
            return $query->where('id', $user->tenant_id);
        }

        return $query->whereRaw('1 = 0');
    }
}

<?php

namespace App\Filament\Resources;

use App\Enums\PaymentGatewayType;
use App\Filament\Resources\PaymentGatewayResource\Pages;
use App\Models\PaymentGateway;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PaymentGatewayResource extends Resource
{
    protected static ?string $model = PaymentGateway::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        $fields = [
            Section::make('Gateway Settings')
                ->description('Configure basic gateway settings')
                ->icon('heroicon-o-cog-6-tooth')
                ->schema([
                    Forms\Components\Select::make('gateway_type')
                        ->label('Payment Gateway')
                        ->options(PaymentGatewayType::options())
                        ->required()
                        ->live()
                        ->disabled(fn (?PaymentGateway $record) => $record !== null)
                        ->helperText('Cannot be changed after creation')
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('display_name')
                        ->label('Custom Display Name')
                        ->maxLength(255)
                        ->helperText('Optional. Leave empty to use default name.'),

                    Forms\Components\TextInput::make('sort_order')
                        ->label('Display Order')
                        ->numeric()
                        ->default(0)
                        ->helperText('Lower numbers appear first'),

                    Forms\Components\Toggle::make('is_enabled')
                        ->label('Enabled')
                        ->default(false)
                        ->helperText('Enable this payment gateway for customer checkout')
                        ->inline(false),

                    Forms\Components\Toggle::make('is_test_mode')
                        ->label('Test Mode (Sandbox)')
                        ->default(true)
                        ->helperText('Use sandbox/test environment for this gateway')
                        ->inline(false),
                ])
                ->columns(2)
                ->collapsible()
                ->columnSpanFull(),
        ];

        // Build API credentials fields for the section
        $credentialFields = [];
        foreach (PaymentGatewayType::cases() as $gatewayType) {
            foreach ($gatewayType->getFieldDefinitions() as $fieldName => $definition) {
                $field = $definition['type'] === 'password'
                    ? Forms\Components\TextInput::make("config.{$fieldName}")
                        ->password()
                        ->revealable()
                    : Forms\Components\TextInput::make("config.{$fieldName}");

                $visibilityBadge = $definition['public']
                    ? '🌐 Public (exposed in API)'
                    : '🔒 Private (server-side only)';

                $field = $field
                    ->label($definition['label'])
                    ->required(fn ($get) => $get('gateway_type') === $gatewayType->value && $definition['required'])
                    ->helperText($definition['hint'] ?? null)
                    ->hint($visibilityBadge)
                    ->visible(fn ($get) => $get('gateway_type') === $gatewayType->value);

                $credentialFields[] = $field;
            }
        }

        // API Credentials Section
        $fields[] = Section::make('API Credentials')
            ->description('Configure your payment gateway credentials')
            ->icon('heroicon-o-key')
            ->schema($credentialFields)
            ->columns(2)
            ->collapsible()
            ->columnSpanFull()
            ->visible(fn ($get) => $get('gateway_type') !== null);

        // Apple Pay Section
        $applePayFields = [
            Forms\Components\Toggle::make('apple_pay_enabled')
                ->label('Enable Apple Pay')
                ->default(false)
                ->live()
                ->helperText('Allow customers to pay with Apple Pay')
                ->inline(false)
                ->columnSpanFull(),
        ];

        foreach (PaymentGatewayType::cases() as $gatewayType) {
            foreach ($gatewayType->getApplePayFields() as $fieldName => $definition) {
                $field = $definition['type'] === 'textarea'
                    ? Forms\Components\Textarea::make("wallet_config.{$fieldName}")
                        ->rows(4)
                    : Forms\Components\TextInput::make("wallet_config.{$fieldName}");

                $field = $field
                    ->label($definition['label'])
                    ->required(fn ($get) => $get('gateway_type') === $gatewayType->value &&
                        $get('apple_pay_enabled') && $definition['required'])
                    ->helperText($definition['hint'] ?? null)
                    ->visible(fn ($get) => $get('gateway_type') === $gatewayType->value && $get('apple_pay_enabled'));

                $applePayFields[] = $field;
            }
        }

        $fields[] = Section::make('Apple Pay')
            ->description('Configure Apple Pay for this gateway')
            ->icon('heroicon-o-device-phone-mobile')
            ->schema($applePayFields)
            ->columns(2)
            ->collapsible()
            ->columnSpanFull()
            ->visible(fn ($get) => PaymentGatewayType::tryFrom($get('gateway_type'))?->supportsApplePay() ?? false);

        // Google Pay Section
        $googlePayFields = [
            Forms\Components\Toggle::make('google_pay_enabled')
                ->label('Enable Google Pay')
                ->default(false)
                ->live()
                ->helperText('Allow customers to pay with Google Pay')
                ->inline(false)
                ->columnSpanFull(),
        ];

        foreach (PaymentGatewayType::cases() as $gatewayType) {
            foreach ($gatewayType->getGooglePayFields() as $fieldName => $definition) {
                $field = Forms\Components\TextInput::make("wallet_config.{$fieldName}")
                    ->label($definition['label'])
                    ->required(fn ($get) => $get('gateway_type') === $gatewayType->value &&
                        $get('google_pay_enabled') && $definition['required'])
                    ->helperText($definition['hint'] ?? null)
                    ->visible(fn ($get) => $get('gateway_type') === $gatewayType->value && $get('google_pay_enabled'));

                $googlePayFields[] = $field;
            }
        }

        $fields[] = Section::make('Google Pay')
            ->description('Configure Google Pay for this gateway')
            ->icon('heroicon-o-device-phone-mobile')
            ->schema($googlePayFields)
            ->columns(2)
            ->collapsible()
            ->columnSpanFull()
            ->visible(fn ($get) => PaymentGatewayType::tryFrom($get('gateway_type'))?->supportsGooglePay() ?? false);

        return $schema->schema($fields);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('gateway_type')
                    ->label('Gateway')
                    ->formatStateUsing(fn (string $state) => PaymentGatewayType::tryFrom($state)?->getLabel() ?? $state)
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('display_name')
                    ->label('Display Name')
                    ->default(fn (PaymentGateway $record) => PaymentGatewayType::tryFrom($record->gateway_type)?->getLabel() ?? '-')
                    ->searchable(),

                Tables\Columns\IconColumn::make('is_enabled')
                    ->label('Enabled')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_test_mode')
                    ->label('Test Mode')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable(),

                Tables\Columns\IconColumn::make('has_valid_config')
                    ->label('Valid Config')
                    ->boolean()
                    ->getStateUsing(fn (PaymentGateway $record) => $record->hasValidConfig())
                    ->tooltip(fn (PaymentGateway $record) => $record->hasValidConfig()
                        ? 'All required fields configured'
                        : 'Missing required credentials'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->filters([
                Tables\Filters\SelectFilter::make('gateway_type')
                    ->label('Gateway Type')
                    ->options(PaymentGatewayType::options()),

                Tables\Filters\TernaryFilter::make('is_enabled')
                    ->label('Enabled')
                    ->boolean(),

                Tables\Filters\TernaryFilter::make('is_test_mode')
                    ->label('Test Mode')
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
            'index' => Pages\ListPaymentGateways::route('/'),
            'create' => Pages\CreatePaymentGateway::route('/create'),
            'edit' => Pages\EditPaymentGateway::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $tenant = filament()->getTenant();
        if ($tenant) {
            $query->where('tenant_id', $tenant->id);
        }

        return $query;
    }

    public static function getNavigationBadge(): ?string
    {
        $tenant = filament()->getTenant();
        if (!$tenant) {
            return null;
        }

        $enabledCount = PaymentGateway::where('tenant_id', $tenant->id)
            ->where('is_enabled', true)
            ->count();

        return $enabledCount > 0 ? (string) $enabledCount : null;
    }
}

<?php

namespace App\Filament\Resources\PackagePricings\Tables;

use App\Models\Country;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class PackagePricingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('country.name')
                    ->label('Country')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('country.symbol')
                    ->label('Currency')
                    ->sortable(),

                TextColumn::make('monthly_price')
                    ->label('Monthly Price')
                    ->money(fn ($record) => $record->country->currency ?? 'USD')
                    ->sortable(),

                TextColumn::make('yearly_price')
                    ->label('Yearly Price')
                    ->money(fn ($record) => $record->country->currency ?? 'USD')
                    ->sortable(),

                TextColumn::make('setup_fee')
                    ->label('Setup Fee')
                    ->money(fn ($record) => $record->country->currency ?? 'USD')
                    ->sortable(),

                TextColumn::make('stripe_monthly_price_id')
                    ->label('Stripe Monthly ID')
                    ->limit(20)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('stripe_yearly_price_id')
                    ->label('Stripe Yearly ID')
                    ->limit(20)
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('country_code')
                    ->label('Country')
                    ->options(Country::pluck('name', 'code'))
                    ->searchable(),

                TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('country.name');
    }
}

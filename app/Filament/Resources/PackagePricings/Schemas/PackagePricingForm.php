<?php

namespace App\Filament\Resources\PackagePricings\Schemas;

use App\Models\Country;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PackagePricingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('country_code')
                    ->label('Country')
                    ->options(Country::pluck('name', 'code'))
                    ->required()
                    ->searchable()
                    ->reactive(),

                TextInput::make('monthly_price')
                    ->label('Monthly Price')
                    ->numeric()
                    ->required()
                    ->step(0.01)
                    ->prefix(function ($get) {
                        $countryCode = $get('country_code');
                        if ($countryCode) {
                            $country = Country::where('code', $countryCode)->first();

                            return $country?->symbol ?? '$';
                        }

                        return '$';
                    })
                    ->helperText('Enter monthly price'),

                TextInput::make('yearly_price')
                    ->label('Yearly Price')
                    ->numeric()
                    ->required()
                    ->step(0.01)
                    ->prefix(function ($get) {
                        $countryCode = $get('country_code');
                        if ($countryCode) {
                            $country = Country::where('code', $countryCode)->first();

                            return $country?->symbol ?? '$';
                        }

                        return '$';
                    })
                    ->helperText('Usually 30% off monthly price × 12'),

                TextInput::make('setup_fee')
                    ->label('One-time Setup Fee')
                    ->numeric()
                    ->required()
                    ->default(0)
                    ->step(0.01)
                    ->prefix(function ($get) {
                        $countryCode = $get('country_code');
                        if ($countryCode) {
                            $country = Country::where('code', $countryCode)->first();

                            return $country?->symbol ?? '$';
                        }

                        return '$';
                    })
                    ->helperText('Charged once with the first payment. 0 for no setup fee'),

                TextInput::make('stripe_monthly_price_id')
                    ->label('Stripe Monthly Price ID')
                    ->maxLength(255)
                    ->helperText('From Stripe dashboard'),

                TextInput::make('stripe_yearly_price_id')
                    ->label('Stripe Yearly Price ID')
                    ->maxLength(255)
                    ->helperText('From Stripe dashboard'),

                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->required(),
            ]);
    }
}

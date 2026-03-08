<?php

namespace App\Filament\Resources\AddonPricings\Pages;

use App\Filament\Resources\AddonPricings\AddonPricingResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAddonPricing extends EditRecord
{
    protected static string $resource = AddonPricingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

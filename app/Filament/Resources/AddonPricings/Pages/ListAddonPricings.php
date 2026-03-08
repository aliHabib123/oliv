<?php

namespace App\Filament\Resources\AddonPricings\Pages;

use App\Filament\Resources\AddonPricings\AddonPricingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAddonPricings extends ListRecords
{
    protected static string $resource = AddonPricingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

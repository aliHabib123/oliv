<?php

namespace App\Filament\Resources\PackagePricings\Pages;

use App\Filament\Resources\PackagePricings\PackagePricingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPackagePricings extends ListRecords
{
    protected static string $resource = PackagePricingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

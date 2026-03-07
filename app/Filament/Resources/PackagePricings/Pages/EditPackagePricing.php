<?php

namespace App\Filament\Resources\PackagePricings\Pages;

use App\Filament\Resources\PackagePricings\PackagePricingResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPackagePricing extends EditRecord
{
    protected static string $resource = PackagePricingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

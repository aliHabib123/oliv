<?php

namespace App\Filament\Resources\CardBrandResource\Pages;

use App\Filament\Resources\CardBrandResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCardBrand extends EditRecord
{
    protected static string $resource = CardBrandResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

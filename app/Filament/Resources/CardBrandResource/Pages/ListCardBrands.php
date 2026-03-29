<?php

namespace App\Filament\Resources\CardBrandResource\Pages;

use App\Filament\Resources\CardBrandResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCardBrands extends ListRecords
{
    protected static string $resource = CardBrandResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

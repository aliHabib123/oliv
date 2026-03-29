<?php

namespace App\Filament\Resources\CardBrandResource\Pages;

use App\Filament\Resources\CardBrandResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateCardBrand extends CreateRecord
{
    protected static string $resource = CardBrandResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $tenant = filament()->getTenant();
        $data['tenant_id'] = $tenant?->id ?? Auth::user()->tenant_id;

        // Set name from card brand options based on slug
        $options = CardBrandResource::getCardBrandOptions();
        $data['name'] = $options[$data['slug']] ?? $data['slug'];

        return $data;
    }
}

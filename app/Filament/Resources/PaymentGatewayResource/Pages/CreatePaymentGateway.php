<?php

namespace App\Filament\Resources\PaymentGatewayResource\Pages;

use App\Filament\Resources\PaymentGatewayResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreatePaymentGateway extends CreateRecord
{
    protected static string $resource = PaymentGatewayResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $tenant = filament()->getTenant();
        $data['tenant_id'] = $tenant?->id ?? Auth::user()->tenant_id;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

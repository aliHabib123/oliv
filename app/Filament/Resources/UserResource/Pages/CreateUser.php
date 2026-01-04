<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $authUser = auth()->user();

        if ($authUser && ! $authUser->hasRole('super_admin')) {
            $data['tenant_id'] = $authUser->tenant_id;
        }

        unset($data['roles']);

        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var User $user */
        $user = $this->record;

        $roles = $this->data['roles'] ?? [];

        if (! empty($roles)) {
            $user->syncRoles($roles);
        }
    }
}

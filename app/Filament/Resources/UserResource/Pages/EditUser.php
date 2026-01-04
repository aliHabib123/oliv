<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $authUser = auth()->user();

        if ($authUser && ! $authUser->hasRole('super_admin')) {
            $data['tenant_id'] = $this->record->tenant_id;
        }

        unset($data['roles']);

        return $data;
    }

    protected function afterSave(): void
    {
        /** @var User $user */
        $user = $this->record;

        $roles = $this->data['roles'] ?? [];

        if (! empty($roles)) {
            $user->syncRoles($roles);
        }
    }
}

<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Pages\EditRecordAndReturnToList as EditRecord;
use App\Filament\Resources\Roles\RoleResource;
use App\Models\Role;
use App\Support\UserPermissions;
use Filament\Actions\DeleteAction;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Role $role */
        $role = $this->getRecord();

        return [
            ...$data,
            ...UserPermissions::formStateFromPermissions($role->menu_permissions ?? []),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $permissions = UserPermissions::extractFormState($data, auth()->user());
        $data = UserPermissions::removeFormState($data);
        $data['menu_permissions'] = $permissions;

        if (! auth()->user()?->canViewAllCountries()) {
            $data['location_id'] = auth()->user()?->location_id;
        }

        return $data;
    }
}

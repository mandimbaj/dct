<?php

namespace App\Filament\Resources\Permissions\Pages;

use App\Filament\Resources\Permissions\PermissionResource;
use App\Filament\Resources\Users\Pages\Concerns\ManagesUserPermissions;
use App\Models\User;
use Filament\Resources\Pages\EditRecord;

class EditPermission extends EditRecord
{
    use ManagesUserPermissions;

    protected static string $resource = PermissionResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var User $user */
        $user = $this->getRecord();

        return $this->withPermissionFormState($user, $data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->capturePermissions($data);
    }

    protected function afterSave(): void
    {
        /** @var User $user */
        $user = $this->getRecord();

        $this->syncCapturedPermissions($user);
    }
}

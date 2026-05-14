<?php

namespace App\Filament\Resources\Users\Pages\Concerns;

use App\Models\User;
use App\Support\UserPermissions;

trait ManagesUserPermissions
{
    /**
     * @var array<string, array<int, string>>
     */
    protected array $capturedPermissions = [];

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function withPermissionFormState(User $user, array $data): array
    {
        return [
            ...$data,
            ...UserPermissions::formState($user),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function capturePermissions(array $data): array
    {
        $this->capturedPermissions = UserPermissions::extractFormState($data, auth()->user());

        return UserPermissions::removeFormState($data);
    }

    protected function syncCapturedPermissions(User $user): void
    {
        UserPermissions::sync($user, $this->capturedPermissions);
    }
}

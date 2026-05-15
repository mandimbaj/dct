<?php

namespace App\Filament\Resources\Users\Pages\Concerns;

use App\Models\Role;

trait ManagesUserRole
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function normalizeAssignableRole(array $data): array
    {
        if (blank($data['role_id'] ?? null)) {
            $data['role_id'] = null;

            return $data;
        }

        $role = Role::query()
            ->with('location.translations')
            ->find($data['role_id']);

        if (! $role?->canBeAssignedBy(auth()->user())) {
            $data['role_id'] = null;
        }

        return $data;
    }
}

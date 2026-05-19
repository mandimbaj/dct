<?php

namespace App\Models;

use App\Support\UserPermissions;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'description', 'location_id', 'menu_permissions', 'is_system'])]
class Role extends Model
{
    protected function casts(): array
    {
        return [
            'menu_permissions' => 'array',
            'is_system' => 'boolean',
        ];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'location_id', 'location_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function canBeManagedBy(?User $actor): bool
    {
        if (! $actor) {
            return false;
        }

        if ($actor->canViewAllCountries()) {
            return true;
        }

        return filled($actor->location_id)
            && (int) $this->location_id === (int) $actor->location_id
            && $this->permissionsFitWithin($actor);
    }

    public function canBeAssignedBy(?User $actor): bool
    {
        if (! $actor) {
            return false;
        }

        if ($actor->canViewAllCountries()) {
            return true;
        }

        if (blank($actor->location_id)) {
            return false;
        }

        if (filled($this->location_id) && (int) $this->location_id !== (int) $actor->location_id) {
            return false;
        }

        return $this->permissionsFitWithin($actor);
    }

    public function displayName(): string
    {
        $scope = $this->location?->display_name;

        return filled($scope) ? "{$this->name} ({$scope})" : $this->name;
    }

    private function permissionsFitWithin(User $actor): bool
    {
        $rolePermissions = UserPermissions::normalize($this->menu_permissions ?? []);
        $actorPermissions = UserPermissions::permissionsFor($actor);

        foreach (UserPermissions::actions() as $action) {
            if (array_diff($rolePermissions[$action] ?? [], $actorPermissions[$action] ?? []) !== []) {
                return false;
            }
        }

        return true;
    }
}

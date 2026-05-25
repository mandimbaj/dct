<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Permissions\PermissionResource;
use App\Filament\Resources\Roles\RoleResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRoles extends ListRecords
{
    protected static string $resource = RoleResource::class;

    public function getTitle(): string
    {
        return __('aho.auth_management.role_permissions_navigation');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('direct_user_permissions')
                ->label(__('aho.auth_management.direct_permissions'))
                ->icon('heroicon-o-lock-closed')
                ->color('gray')
                ->visible(fn (): bool => PermissionResource::canAccess())
                ->url(fn (): string => PermissionResource::getUrl('index')),
        ];
    }
}

<?php

namespace App\Filament\Resources;

use App\Support\TableExportActions;
use App\Support\UserPermissions;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

abstract class AhoResource extends Resource
{
    public static function canAccess(): bool
    {
        return static::canUsePermission(UserPermissions::ACTION_VIEW);
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    public static function canView(Model $record): bool
    {
        return static::canUsePermission(UserPermissions::ACTION_VIEW);
    }

    public static function canCreate(): bool
    {
        return static::canUsePermission(UserPermissions::ACTION_CREATE);
    }

    public static function canEdit(Model $record): bool
    {
        return static::canUsePermission(UserPermissions::ACTION_UPDATE);
    }

    public static function canDelete(Model $record): bool
    {
        return static::canUsePermission(UserPermissions::ACTION_DELETE);
    }

    public static function canDeleteAny(): bool
    {
        return static::canUsePermission(UserPermissions::ACTION_DELETE);
    }

    public static function configureTable(Table $table): void
    {
        parent::configureTable($table);

        if (static::getGloballySearchableAttributes() !== []) {
            $table->pushRecordActions([
                ViewAction::make(),
            ]);
        }

        TableExportActions::appendTo($table);
    }

    protected static function canUsePermission(string $action): bool
    {
        $user = auth()->user();

        return (bool) $user
            && UserPermissions::allowsResource($user, static::class, $action);
    }
}

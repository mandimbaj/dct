<?php

namespace App\Filament\Resources;

use App\Support\TableExportActions;
use App\Support\UserPermissions;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Throwable;

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

        $table->contentFooter(fn (HasTable $livewire) => view('filament.tables.record-count-footer', [
            'total' => static::tableRecordCount($livewire),
        ]));
    }

    protected static function canUsePermission(string $action): bool
    {
        $user = auth()->user();

        return (bool) $user
            && UserPermissions::allowsResource($user, static::class, $action);
    }

    protected static function tableRecordCount(HasTable $livewire): int
    {
        try {
            $records = $livewire->getTableRecords();

            if (method_exists($records, 'total')) {
                return (int) $records->total();
            }

            if (method_exists($records, 'count')) {
                return (int) $records->count();
            }
        } catch (Throwable) {
        }

        try {
            return (int) (clone $livewire->getTableQueryForExport())->count();
        } catch (Throwable) {
            try {
                return (int) (clone $livewire->getTableQuery())->count();
            } catch (Throwable) {
                return 0;
            }
        }
    }
}

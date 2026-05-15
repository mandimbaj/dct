<?php

namespace App\Filament\Resources\UhcClockThemes\Pages;

use App\Filament\Resources\UhcClockThemes\UhcClockThemeResource;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\Pages\EditRecordAndReturnToList as EditRecord;

class EditUhcClockTheme extends EditRecord
{
    protected static string $resource = UhcClockThemeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

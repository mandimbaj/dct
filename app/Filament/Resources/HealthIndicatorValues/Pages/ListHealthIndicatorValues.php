<?php

namespace App\Filament\Resources\HealthIndicatorValues\Pages;

use App\Filament\Imports\HealthIndicatorValueImporter;
use App\Filament\Resources\HealthIndicatorValues\HealthIndicatorValueResource;
use App\Support\UserPermissions;
use Filament\Actions\CreateAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;

class ListHealthIndicatorValues extends ListRecords
{
    protected static string $resource = HealthIndicatorValueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ImportAction::make()
                ->label(__('aho.actions.import_csv'))
                ->importer(HealthIndicatorValueImporter::class)
                ->visible(fn (): bool => (bool) auth()->user() && UserPermissions::allowsResource(auth()->user(), HealthIndicatorValueResource::class, UserPermissions::ACTION_IMPORT)),
            CreateAction::make()
                ->label(__('aho.actions.add_indicator_value')),
        ];
    }
}

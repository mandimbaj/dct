<?php

namespace App\Filament\Resources\UhcClockFacts\Pages;

use App\Filament\Clusters\DataQuality\Pages\IndicatorQualityChecks;
use App\Filament\Resources\DqaValidCategoryOptions\DqaValidCategoryOptionResource;
use App\Filament\Resources\DqaValidDataSources\DqaValidDataSourceResource;
use App\Filament\Resources\DqaValidMeasureTypes\DqaValidMeasureTypeResource;
use App\Filament\Resources\UhcClockFacts\UhcClockFactResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListUhcClockFacts extends ListRecords
{
    protected static string $resource = UhcClockFactResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('checkDataQuality')
                ->label(__('aho.actions.check_data_quality'))
                ->icon(Heroicon::OutlinedShieldCheck)
                ->url(IndicatorQualityChecks::getUrl())
                ->visible(fn (): bool => IndicatorQualityChecks::canAccess()),
            ActionGroup::make([
                Action::make('validDataSources')
                    ->label(DqaValidDataSourceResource::getNavigationLabel())
                    ->icon(Heroicon::OutlinedCircleStack)
                    ->url(DqaValidDataSourceResource::getUrl()),
                Action::make('validMeasureTypes')
                    ->label(DqaValidMeasureTypeResource::getNavigationLabel())
                    ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
                    ->url(DqaValidMeasureTypeResource::getUrl()),
                Action::make('validCategoryOptions')
                    ->label(DqaValidCategoryOptionResource::getNavigationLabel())
                    ->icon(Heroicon::OutlinedListBullet)
                    ->url(DqaValidCategoryOptionResource::getUrl()),
            ])
                ->label(__('aho.actions.load_validators'))
                ->icon(Heroicon::OutlinedCircleStack)
                ->button()
                ->color('gray'),
        ];
    }
}

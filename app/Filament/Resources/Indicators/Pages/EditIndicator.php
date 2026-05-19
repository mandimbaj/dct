<?php

namespace App\Filament\Resources\Indicators\Pages;

use App\Filament\Resources\Indicators\IndicatorResource;
use App\Filament\Resources\Pages\EditRecordAndReturnToList as EditRecord;
use App\Support\ResourceTranslations;
use Filament\Actions\DeleteAction;

class EditIndicator extends EditRecord
{
    protected static string $resource = IndicatorResource::class;

    /**
     * @var array<string, mixed>
     */
    private array $translationData = [];

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return ResourceTranslations::fill($data, $this->record, [
            'name',
            'shortname',
            'definition',
            'preferred_datasources',
            'numerator_description',
            'denominator_description',
        ]);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->translationData = ResourceTranslations::extract($data, [
            'name',
            'shortname',
            'definition',
            'preferred_datasources',
            'numerator_description',
            'denominator_description',
        ]);

        return $data;
    }

    protected function afterSave(): void
    {
        ResourceTranslations::sync($this->record, $this->translationData);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

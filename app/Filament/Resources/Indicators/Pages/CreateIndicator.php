<?php

namespace App\Filament\Resources\Indicators\Pages;

use App\Filament\Resources\Indicators\IndicatorResource;
use App\Support\ResourceTranslations;
use Filament\Resources\Pages\CreateRecord;

class CreateIndicator extends CreateRecord
{
    protected static string $resource = IndicatorResource::class;

    /**
     * @var array<string, mixed>
     */
    private array $translationData = [];

    protected function mutateFormDataBeforeCreate(array $data): array
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

    protected function afterCreate(): void
    {
        ResourceTranslations::sync($this->record, $this->translationData);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

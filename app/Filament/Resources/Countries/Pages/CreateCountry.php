<?php

namespace App\Filament\Resources\Countries\Pages;

use App\Filament\Resources\Countries\CountryResource;
use App\Support\ResourceTranslations;
use Filament\Resources\Pages\CreateRecord;

class CreateCountry extends CreateRecord
{
    protected static string $resource = CountryResource::class;

    /**
     * @var array<string, mixed>
     */
    private array $translationData = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->translationData = ResourceTranslations::extract($data, [
            'name',
            'description',
            'latitude',
            'longitude',
            'cordinate',
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

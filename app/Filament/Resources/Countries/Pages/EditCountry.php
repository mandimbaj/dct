<?php

namespace App\Filament\Resources\Countries\Pages;

use App\Filament\Resources\Countries\CountryResource;
use App\Filament\Resources\Pages\EditRecordAndReturnToList as EditRecord;
use App\Support\ResourceTranslations;
use Filament\Actions\DeleteAction;

class EditCountry extends EditRecord
{
    protected static string $resource = CountryResource::class;

    /**
     * @var array<string, mixed>
     */
    private array $translationData = [];

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return ResourceTranslations::fill($data, $this->record, [
            'name',
            'description',
            'latitude',
            'longitude',
            'cordinate',
        ]);
    }

    protected function mutateFormDataBeforeSave(array $data): array
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

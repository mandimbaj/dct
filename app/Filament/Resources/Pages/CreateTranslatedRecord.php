<?php

namespace App\Filament\Resources\Pages;

use App\Support\ResourceTranslations;
use Filament\Resources\Pages\CreateRecord;

abstract class CreateTranslatedRecord extends CreateRecord
{
    /**
     * @var array<string, mixed>
     */
    private array $translationData = [];

    /**
     * @return array<int, string>
     */
    protected function translationFields(): array
    {
        return property_exists(static::class, 'translationFields')
            ? static::$translationFields
            : ['name', 'shortname', 'description'];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->translationData = ResourceTranslations::extract($data, $this->translationFields());

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

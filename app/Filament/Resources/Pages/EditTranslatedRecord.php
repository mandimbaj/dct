<?php

namespace App\Filament\Resources\Pages;

use App\Support\ResourceTranslations;
use Filament\Actions\DeleteAction;

abstract class EditTranslatedRecord extends EditRecordAndReturnToList
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

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return ResourceTranslations::fill($data, $this->record, $this->translationFields());
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->translationData = ResourceTranslations::extract($data, $this->translationFields());

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

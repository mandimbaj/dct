<?php

namespace App\Filament\Resources\NationalObservatories\Pages;

use App\Filament\Resources\NationalObservatories\NationalObservatoryResource;
use App\Models\NationalObservatory;
use App\Support\NationalObservatoryNotifier;
use Filament\Resources\Pages\CreateRecord;

class CreateNationalObservatory extends CreateRecord
{
    protected static string $resource = NationalObservatoryResource::class;

    public function mount(): void
    {
        $existing = NationalObservatoryResource::existingForCurrentUserCountry();

        if ($existing) {
            $this->redirect($this->getResource()::getUrl('edit', ['record' => $existing]));

            return;
        }

        parent::mount();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return NationalObservatoryResource::prepareCreateData($data);
    }

    protected function afterCreate(): void
    {
        /** @var NationalObservatory $observatory */
        $observatory = $this->getRecord();

        NationalObservatoryNotifier::record(NationalObservatoryNotifier::ACTION_CREATED, $observatory);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

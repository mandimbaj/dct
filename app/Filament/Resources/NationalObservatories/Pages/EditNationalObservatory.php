<?php

namespace App\Filament\Resources\NationalObservatories\Pages;

use App\Filament\Resources\NationalObservatories\NationalObservatoryResource;
use App\Filament\Resources\Pages\EditRecordAndReturnToList as EditRecord;
use App\Models\NationalObservatory;
use App\Support\NationalObservatoryNotifier;
use Filament\Actions\DeleteAction;

class EditNationalObservatory extends EditRecord
{
    protected static string $resource = NationalObservatoryResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return NationalObservatoryResource::prepareSaveData($data, $this->getRecord());
    }

    protected function afterSave(): void
    {
        /** @var NationalObservatory $observatory */
        $observatory = $this->getRecord();

        NationalObservatoryNotifier::record(NationalObservatoryNotifier::ACTION_UPDATED, $observatory);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->after(fn (NationalObservatory $record): mixed => NationalObservatoryNotifier::record(
                    NationalObservatoryNotifier::ACTION_DELETED,
                    $record,
                )),
        ];
    }
}

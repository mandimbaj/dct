<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Pages\EditRecordAndReturnToList as EditRecord;
use App\Filament\Resources\Users\Pages\Concerns\ManagesUserRole;
use App\Filament\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;

class EditUser extends EditRecord
{
    use ManagesUserRole;

    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! auth()->user()?->canViewAllCountries()) {
            $data['location_id'] = auth()->user()?->location_id;
            $data['is_super_admin'] = false;
            $data['is_country_admin'] = false;
        }

        return $this->normalizeAssignableRole($data);
    }
}

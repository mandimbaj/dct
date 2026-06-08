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
        $data['is_country_admin'] = false;

        if (! auth()->user()?->is_super_admin) {
            $data['location_id'] = auth()->user()?->location_id;
            $data['is_super_admin'] = false;
            $data['can_view_all_countries'] = false;
        }

        if (($data['is_super_admin'] ?? false) || ($data['can_view_all_countries'] ?? false)) {
            $data['location_id'] = null;
        }

        return $this->normalizeAssignableRole($data);
    }
}

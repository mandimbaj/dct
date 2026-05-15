<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\Pages\Concerns\ManagesUserRole;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use App\Notifications\MessageReceived;
use App\Support\NotificationRecipients;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Notification;

class CreateUser extends CreateRecord
{
    use ManagesUserRole;

    protected static string $resource = UserResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (! auth()->user()?->canViewAllCountries()) {
            $data['location_id'] = auth()->user()?->location_id;
            $data['is_super_admin'] = false;
            $data['is_country_admin'] = false;
        }

        return $this->normalizeAssignableRole($data);
    }

    protected function afterCreate(): void
    {
        /** @var User $user */
        $user = $this->getRecord();

        if (! auth()->user()?->canViewAllCountries() && $user->location_id) {
            $countryCode = optional($user->location)->iso_alpha;
            $title = __('aho.notifications.messages.created_account_title');
            $body = __('aho.notifications.messages.created_account_body', [
                'name' => $user->name,
                'email' => $user->email,
                'country' => $countryCode ? strtoupper($countryCode) : __('aho.notifications.messages.country_unknown'),
            ]);

            Notification::send(
                NotificationRecipients::forCountry($user->location_id, $user->id),
                new MessageReceived($title, $body, $countryCode),
            );
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

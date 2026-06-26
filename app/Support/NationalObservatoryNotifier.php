<?php

namespace App\Support;

use App\Models\NationalObservatory;
use App\Models\User;
use App\Notifications\MessageReceived;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Throwable;

class NationalObservatoryNotifier
{
    public const ACTION_CREATED = 'created';

    public const ACTION_UPDATED = 'updated';

    public const ACTION_DELETED = 'deleted';

    public static function record(string $action, NationalObservatory $observatory): void
    {
        $actor = auth()->user();
        $countryCode = self::countryCode($observatory);
        $title = __('aho.notifications.messages.national_observatory_title', [
            'action' => __('aho.notifications.messages.national_observatory_actions.'.$action),
        ]);
        $body = __('aho.notifications.messages.national_observatory_body', [
            'action' => __('aho.notifications.messages.national_observatory_actions.'.$action),
            'actor' => $actor?->name ?: __('aho.notifications.messages.actor_unknown'),
            'country' => $countryCode ? strtoupper($countryCode) : __('aho.notifications.messages.country_unknown'),
            'email' => $actor?->email ?: __('aho.fields.not_available'),
            'observatory' => $observatory->display_name,
        ]);

        $notification = new MessageReceived($title, $body, $countryCode);
        $recipients = self::recipients($actor);

        try {
            if ($recipients->isNotEmpty()) {
                Notification::send($recipients, $notification);

                $recipients->each(fn (User $recipient): mixed => TopbarAlerts::forgetForUser($recipient, $countryCode));
            }

            self::notifyConfiguredAdminEmail($notification, $recipients);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    /**
     * @return Collection<int, User>
     */
    private static function recipients(?User $actor): Collection
    {
        /** @var EloquentCollection<int, User> $admins */
        $admins = User::query()
            ->where(function ($query): void {
                $query
                    ->where('is_super_admin', true)
                    ->orWhere('can_view_all_countries', true);
            })
            ->get();

        return $admins
            ->when($actor instanceof User && $actor->exists, fn (Collection $users): Collection => $users->push($actor))
            ->unique(fn (User $user): int|string => $user->getKey())
            ->values();
    }

    /**
     * @param  Collection<int, User>  $recipients
     */
    private static function notifyConfiguredAdminEmail(MessageReceived $notification, Collection $recipients): void
    {
        $adminEmail = trim((string) config('aho.admin.email'));

        if ($adminEmail === '') {
            return;
        }

        $alreadySent = $recipients
            ->contains(fn (User $user): bool => strcasecmp((string) $user->email, $adminEmail) === 0);

        if ($alreadySent) {
            return;
        }

        Notification::route('mail', $adminEmail)->notify($notification);
    }

    private static function countryCode(NationalObservatory $observatory): ?string
    {
        $country = $observatory->relationLoaded('location')
            ? $observatory->location
            : $observatory->location()->first(['location_id', 'iso_alpha']);

        $code = strtolower(trim((string) $country?->iso_alpha));

        return $code !== '' ? substr($code, 0, 2) : null;
    }
}

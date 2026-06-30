<?php

namespace App\Support;

use App\Models\Country;
use App\Models\FailedImportRow;
use App\Models\UserPageVisit;
use App\Notifications\SystemNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Throwable;

class AdminActivityNotifier
{
    private const IGNORED_FIELDS = [
        'updated_at',
        'date_lastupdated',
        'remember_token',
        'password',
    ];

    private const IGNORED_MODELS = [
        FailedImportRow::class,
        UserPageVisit::class,
    ];

    public static function observeApplicationModels(): void
    {
        if (! config('aho.notifications.activity_enabled', true)) {
            return;
        }

        foreach (['created', 'updated', 'deleted'] as $modelEvent) {
            Event::listen("eloquent.{$modelEvent}: *", function (string $eventName, array $payload) use ($modelEvent): void {
                $model = $payload[0] ?? null;

                if ($model instanceof Model) {
                    self::record($modelEvent, $model);
                }
            });
        }
    }

    public static function record(string $event, Model $model): void
    {
        if (! self::shouldNotify($event, $model)) {
            return;
        }

        $adminEmail = trim((string) config('aho.admin.email'));

        if ($adminEmail === '' || ! config('aho.notifications.mail_enabled', true)) {
            return;
        }

        try {
            Notification::route('mail', $adminEmail)->notify(new SystemNotification(
                self::title($event, $model),
                self::body($event, $model),
                self::countryCode($model),
            ));
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private static function shouldNotify(string $event, Model $model): bool
    {
        if (in_array($model::class, self::IGNORED_MODELS, true) || str_ends_with($model::class, 'Translation')) {
            return false;
        }

        return $event !== 'updated' || self::changedFields($model) !== [];
    }

    private static function title(string $event, Model $model): string
    {
        return __('aho.notifications.system.admin_activity_title', [
            'action' => __('aho.notifications.system.actions.'.$event),
            'model' => Str::headline(class_basename($model)),
        ]);
    }

    private static function body(string $event, Model $model): string
    {
        $parts = [
            __('aho.notifications.system.admin_activity_record', [
                'record' => self::recordLabel($model),
                'key' => (string) $model->getKey(),
            ]),
        ];

        $user = auth()->user();

        if ($user) {
            $parts[] = __('aho.notifications.system.admin_activity_actor', [
                'name' => $user->name,
                'email' => $user->email,
            ]);
        }

        if ($event === 'updated') {
            $parts[] = __('aho.notifications.system.admin_activity_fields', [
                'fields' => implode(', ', self::changedFields($model)),
            ]);
        }

        $country = self::countryCode($model);

        if ($country) {
            $parts[] = __('aho.notifications.system.admin_activity_country', ['country' => strtoupper($country)]);
        }

        return implode("\n", $parts);
    }

    private static function recordLabel(Model $model): string
    {
        foreach (['display_name', 'display_title', 'name', 'title', 'code', 'afrocode', 'email'] as $attribute) {
            $value = $model->getAttribute($attribute);

            if (filled($value)) {
                return Str::headline(class_basename($model)).' - '.$value;
            }
        }

        return Str::headline(class_basename($model));
    }

    /**
     * @return array<int, string>
     */
    private static function changedFields(Model $model): array
    {
        return collect(array_keys($model->getChanges()))
            ->reject(fn (string $field): bool => in_array($field, self::IGNORED_FIELDS, true))
            ->values()
            ->all();
    }

    private static function countryCode(Model $model): ?string
    {
        $locationId = $model->getAttribute('location_id');

        if (! $locationId) {
            return null;
        }

        try {
            $country = Country::query()->find($locationId);

            return $country?->iso_alpha ? strtolower((string) $country->iso_alpha) : null;
        } catch (Throwable) {
            return null;
        }
    }
}

<?php

namespace App\Support;

use App\Models\Country;
use App\Models\HealthIndicatorValue;
use App\Models\User;
use App\Notifications\MessageReceived;
use App\Notifications\SystemNotification;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

class TopbarAlerts
{
    private static ?bool $hasNotificationsTable = null;

    private const CACHE_SECONDS = 60;

    /**
     * @return array{
     *     unreadMessages: int,
     *     unreadSystemNotifications: int,
     *     pendingValidationCount: int,
     *     latestMessages: Collection<int, DatabaseNotification>,
     *     latestSystemNotifications: Collection<int, DatabaseNotification>
     * }
     */
    private const NOTIFICATION_TYPES = [
        MessageReceived::class,
        SystemNotification::class,
    ];

    public static function forgetForUser(User $user, ?string $country): void
    {
        Cache::forget(sprintf(
            'topbar-alerts.user.%s.%s.counts',
            $user->getKey(),
            self::normalizeCountry($country ?: optional($user->location)->iso_alpha ?: 'global'),
        ));

        foreach (self::NOTIFICATION_TYPES as $type) {
            Cache::forget(self::latestCacheKey($user, self::normalizeCountry($country ?: optional($user->location)->iso_alpha ?: 'global'), $type));
        }
    }

    public static function forUser(?User $user, ?string $country): array
    {
        $country = self::normalizeCountry($country ?: optional($user?->location)->iso_alpha ?: 'global');
        $empty = [
            'unreadMessages' => 0,
            'unreadSystemNotifications' => 0,
            'pendingValidationCount' => self::pendingValidationCount($country),
            'latestMessages' => collect(),
            'latestSystemNotifications' => collect(),
        ];

        if (! $user || ! self::hasNotificationsTable()) {
            return $empty;
        }

        $cacheKey = sprintf('topbar-alerts.user.%s.%s.counts', $user->getKey(), $country);

        try {
            $counts = Cache::remember(
                $cacheKey,
                now()->addSeconds(self::CACHE_SECONDS),
                fn (): array => self::notificationsCounts($user, $country),
            );

            $unreadMessages = $counts[MessageReceived::class] ?? 0;
            $unreadSystemNotifications = $counts[SystemNotification::class] ?? 0;

            return [
                'unreadMessages' => $unreadMessages,
                'unreadSystemNotifications' => $unreadSystemNotifications,
                'pendingValidationCount' => $empty['pendingValidationCount'],
                'latestMessages' => $unreadMessages > 0 ? self::latestNotifications($user, MessageReceived::class, $country) : collect(),
                'latestSystemNotifications' => $unreadSystemNotifications > 0 ? self::latestNotifications($user, SystemNotification::class, $country) : collect(),
            ];
        } catch (Throwable) {
            return $empty;
        }
    }

    private static function hasNotificationsTable(): bool
    {
        if (self::$hasNotificationsTable !== null) {
            return self::$hasNotificationsTable;
        }

        try {
            return self::$hasNotificationsTable = Schema::hasTable('notifications');
        } catch (Throwable) {
            return self::$hasNotificationsTable = false;
        }
    }

    private static function notificationsQuery(User $user, string $type, string $country)
    {
        return $user->notifications()
            ->select(['id', 'type', 'data', 'created_at'])
            ->whereNull('read_at')
            ->where('type', $type)
            ->when($country !== 'global', function ($query) use ($country): void {
                $query->where(function ($query) use ($country): void {
                    $query->where('data->country', $country)
                        ->orWhere('data->country_code', $country)
                        ->orWhere('data->iso_alpha', $country);
                });
            })
            ->latest();
    }

    /**
     * @return array<string, int>
     */
    private static function notificationsCounts(User $user, string $country): array
    {
        return self::notificationsGroupedQuery($user, $country)
            ->pluck('aggregate', 'type')
            ->all();
    }

    private static function notificationsGroupedQuery(User $user, string $country)
    {
        return $user->notifications()
            ->whereNull('read_at')
            ->whereIn('type', self::NOTIFICATION_TYPES)
            ->when($country !== 'global', function ($query) use ($country): void {
                $query->where(function ($query) use ($country): void {
                    $query->where('data->country', $country)
                        ->orWhere('data->country_code', $country)
                        ->orWhere('data->iso_alpha', $country);
                });
            })
            ->selectRaw('type, count(*) as aggregate')
            ->groupBy('type');
    }

    /**
     * @return Collection<int, DatabaseNotification>
     */
    private static function latestNotifications(User $user, string $type, string $country): Collection
    {
        return Cache::remember(
            self::latestCacheKey($user, $country, $type),
            now()->addSeconds(self::CACHE_SECONDS),
            fn (): Collection => self::notificationsQuery($user, $type, $country)
                ->limit(5)
                ->get(),
        );
    }

    private static function pendingValidationCount(string $country): int
    {
        $cacheKey = 'topbar-alerts.pending-validation.'.$country;

        try {
            return (int) Cache::remember($cacheKey, now()->addMinutes(5), function () use ($country): int {
                $locationId = self::countryLocationId($country);

                return HealthIndicatorValue::query()
                    ->when($country !== 'global', fn ($query) => filled($locationId)
                        ? $query->where('location_id', $locationId)
                        : $query->whereRaw('1 = 0'))
                    ->where(function ($query): void {
                        $query->where(ApprovalWorkflow::STATUS_COLUMN, ApprovalWorkflow::STATUS_PENDING)
                            ->orWhere(ApprovalWorkflow::MIRROR_COLUMN, ApprovalWorkflow::STATUS_PENDING);
                    })
                    ->count();
            });
        } catch (Throwable) {
            return 0;
        }
    }

    private static function countryLocationId(string $country): ?int
    {
        if ($country === 'global') {
            return null;
        }

        return Cache::remember(
            'topbar-alerts.country-location.'.$country,
            now()->addHour(),
            fn (): ?int => Country::query()
                ->whereRaw('lower(iso_alpha) like ?', [$country.'%'])
                ->value('location_id'),
        );
    }

    private static function latestCacheKey(User $user, string $country, string $type): string
    {
        return sprintf(
            'topbar-alerts.user.%s.%s.latest.%s',
            $user->getKey(),
            $country,
            md5($type),
        );
    }

    private static function normalizeCountry(mixed $country): string
    {
        $country = strtolower(trim((string) $country));

        if ($country === '' || $country === 'global') {
            return 'global';
        }

        return substr($country, 0, 2);
    }
}

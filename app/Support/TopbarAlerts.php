<?php

namespace App\Support;

use App\Models\HealthIndicatorValue;
use App\Models\User;
use App\Notifications\MessageReceived;
use App\Notifications\SystemNotification;
use App\Support\ApprovalWorkflow;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

class TopbarAlerts
{
    private static ?bool $hasNotificationsTable = null;

    /**
     * @return array{
     *     unreadMessages: int,
     *     unreadSystemNotifications: int,
     *     pendingValidationCount: int,
     *     latestMessages: Collection<int, \Illuminate\Notifications\DatabaseNotification>,
     *     latestSystemNotifications: Collection<int, \Illuminate\Notifications\DatabaseNotification>
     * }
     */
    private const NOTIFICATION_TYPES = [
        MessageReceived::class,
        SystemNotification::class,
    ];

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
                now()->addSeconds(10),
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
     * @return Collection<int, \Illuminate\Notifications\DatabaseNotification>
     */
    private static function latestNotifications(User $user, string $type, string $country): Collection
    {
        return self::notificationsQuery($user, $type, $country)
            ->limit(5)
            ->get();
    }

    private static function pendingValidationCount(string $country): int
    {
        $cacheKey = 'topbar-alerts.pending-validation.'.$country;

        try {
            return (int) Cache::remember($cacheKey, now()->addSeconds(30), function () use ($country): int {
                return HealthIndicatorValue::query()
                    ->when($country !== 'global', function ($query) use ($country): void {
                        $query->whereHas('location', function ($query) use ($country): void {
                            $query->where('iso_alpha', 'like', $country.'%');
                        });
                    })
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

    private static function normalizeCountry(mixed $country): string
    {
        $country = strtolower(trim((string) $country));

        if ($country === '' || $country === 'global') {
            return 'global';
        }

        return substr($country, 0, 2);
    }
}

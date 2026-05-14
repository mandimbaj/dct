@php
    $user = auth()->user();
    $hasNotificationsTable = \Illuminate\Support\Facades\Schema::hasTable('notifications');

    $currentCountry = strtolower((string) (request()->route('country') ?: optional($user->location)->iso_alpha ?: 'global'));
    if ($currentCountry !== 'global') {
        $currentCountry = substr($currentCountry, 0, 2);
    }

    $unreadMessages = 0;
    $unreadSystemNotifications = 0;
    $latestMessages = collect();
    $latestSystemNotifications = collect();

    if ($user && $hasNotificationsTable) {
        $notifications = $user->unreadNotifications;

        if ($currentCountry !== 'global') {
            $notifications = $notifications->filter(function ($notification) use ($currentCountry) {
                $country = $notification->data['country'] ?? $notification->data['country_code'] ?? $notification->data['iso_alpha'] ?? null;

                if (! $country) {
                    return false;
                }

                return strtolower(substr(trim((string) $country), 0, 2)) === $currentCountry;
            });
        }

        $messages = $notifications->where('type', 'App\\Notifications\\MessageReceived');
        $system = $notifications->where('type', 'App\\Notifications\\SystemNotification');

        $unreadMessages = $messages->count();
        $unreadSystemNotifications = $system->count();
        $latestMessages = $messages->sortByDesc('created_at')->take(5);
        $latestSystemNotifications = $system->sortByDesc('created_at')->take(5);
    }

    $pendingValidationCount = \App\Models\HealthIndicatorValue::query()
        ->when($currentCountry !== 'global', function ($query) use ($currentCountry) {
            $query->whereHas('location', function ($query) use ($currentCountry) {
                $query->where('iso_alpha', 'like', $currentCountry.'%');
            });
        })
        ->where(function ($query) {
            $query->where('comment', \App\Support\ApprovalWorkflow::STATUS_PENDING)
                ->orWhere('approval_status', \App\Support\ApprovalWorkflow::STATUS_PENDING);
        })
        ->count();
@endphp

<div class="fi-topbar-notifications flex items-center gap-2 h-full">
    <div
        x-data="{ open: false }"
        @click.outside="open = false"
        class="relative fi-topbar-item h-full"
    >
        <x-filament::icon-button
            :badge="$unreadMessages ?: null"
            color="gray"
            :icon="\Filament\Support\Icons\Heroicon::OutlinedEnvelope"
            :icon-alias="\Filament\View\PanelsIconAlias::TOPBAR_OPEN_DATABASE_NOTIFICATIONS_BUTTON"
            icon-size="lg"
            class="fi-topbar-item-btn h-11 w-11"
            type="button"
            @click="open = ! open"
            aria-label="Messages reçus"
        />

        <div
            x-cloak
            x-show="open"
            x-transition
            class="absolute right-0 z-50 mt-2 w-80 max-w-[20rem] overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl"
        >
            <div class="border-b border-gray-100 px-4 py-3">
                <p class="text-sm font-semibold text-gray-900">Messages reçus</p>
                <p class="mt-1 text-xs text-gray-500">{{ $unreadMessages ? __(':count nouveau(x) message(s)', ['count' => $unreadMessages]) : __('Aucun nouveau message reçu') }}</p>
            </div>

            <div class="max-h-72 overflow-y-auto">
                @if ($latestMessages->isEmpty())
                    <div class="p-4 text-sm text-gray-500">Aucun message non lu pour le moment.</div>
                @else
                    @foreach ($latestMessages as $notification)
                        <div class="border-b border-gray-100 px-4 py-3 last:border-b-0">
                            <p class="text-sm font-medium text-gray-900">{{ $notification->data['title'] ?? __('Nouveau message reçu') }}</p>
                            <p class="mt-1 text-sm text-gray-500">{{ \Illuminate\Support\Str::limit($notification->data['body'] ?? $notification->data['message'] ?? '', 80) }}</p>
                        </div>
                    @endforeach
                @endif
            </div>

            <div class="border-t border-gray-100 px-4 py-3 text-right">
                <a href="#" class="text-sm font-medium text-primary-600 hover:text-primary-700">Voir tous les messages</a>
            </div>
        </div>
    </div>

    <div
        x-data="{ open: false }"
        @click.outside="open = false"
        class="relative fi-topbar-item h-full"
    >
        <x-filament::icon-button
            :badge="max($unreadSystemNotifications, $pendingValidationCount) ?: null"
            color="gray"
            :icon="\Filament\Support\Icons\Heroicon::OutlinedBellAlert"
            icon-size="lg"
            class="fi-topbar-item-btn h-11 w-11"
            type="button"
            @click="open = ! open"
            aria-label="Notifications système"
        />

        <div
            x-cloak
            x-show="open"
            x-transition
            class="absolute right-0 z-50 mt-2 w-80 max-w-[20rem] overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl"
        >
            <div class="border-b border-gray-100 px-4 py-3">
                <p class="text-sm font-semibold text-gray-900">Notifications système</p>
                <p class="mt-1 text-xs text-gray-500">{{ $pendingValidationCount ? __(':count élément(s) en attente de validation', ['count' => $pendingValidationCount]) : __('Aucune action système en attente') }}</p>
            </div>

            <div class="max-h-72 overflow-y-auto">
                @if ($latestSystemNotifications->isEmpty())
                    <div class="p-4 text-sm text-gray-500">Aucune notification système non lue.</div>
                @else
                    @foreach ($latestSystemNotifications as $notification)
                        <div class="border-b border-gray-100 px-4 py-3 last:border-b-0">
                            <p class="text-sm font-medium text-gray-900">{{ $notification->data['title'] ?? __('Notification système') }}</p>
                            <p class="mt-1 text-sm text-gray-500">{{ \Illuminate\Support\Str::limit($notification->data['body'] ?? $notification->data['message'] ?? '', 80) }}</p>
                        </div>
                    @endforeach
                @endif
            </div>

            <div class="border-t border-gray-100 px-4 py-3 text-right">
                <a href="{{ \App\Filament\Resources\HealthIndicatorValues\HealthIndicatorValueResource::getUrl('index') }}" class="text-sm font-medium text-primary-600 hover:text-primary-700">Voir les éléments en attente</a>
            </div>
        </div>
    </div>
</div>

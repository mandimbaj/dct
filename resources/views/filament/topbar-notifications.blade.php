@php
    $alerts = \App\Support\TopbarAlerts::forUser(auth()->user(), request()->route('country'));
    $unreadMessages = $alerts['unreadMessages'];
    $unreadSystemNotifications = $alerts['unreadSystemNotifications'];
    $pendingValidationCount = $alerts['pendingValidationCount'];
    $latestMessages = $alerts['latestMessages'];
    $latestSystemNotifications = $alerts['latestSystemNotifications'];
    $country = request()->route('country') ?: 'af';
@endphp

<div
    class="fi-topbar-notifications"
>
    <x-filament::dropdown placement="bottom-end" width="md" teleport class="fi-topbar-item">
        <x-slot name="trigger">
            <button
                type="button"
                class="fi-topbar-item-btn"
                aria-label="{{ __('aho.notifications.messages.aria_label') }}"
            >
                {{ \Filament\Support\generate_icon_html(\Filament\Support\Icons\Heroicon::OutlinedEnvelope, attributes: (new \Illuminate\View\ComponentAttributeBag)->class(['fi-topbar-item-icon'])) }}

                @if ($unreadMessages)
                    <x-filament::badge size="sm" class="ml-2">{{ $unreadMessages }}</x-filament::badge>
                @endif
            </button>
        </x-slot>

        <x-filament::dropdown.list>
            <div class="fi-dropdown-header px-4 py-3 border-b border-gray-100">
                <p class="text-sm font-semibold text-gray-900">{{ __('aho.notifications.messages.title') }}</p>
                <p class="mt-1 text-xs text-gray-500">{{ trans_choice('aho.notifications.messages.summary', $unreadMessages, ['count' => $unreadMessages]) }}</p>
            </div>

            @if ($latestMessages->isEmpty())
                <x-filament::dropdown.list.item disabled>
                    <span class="text-sm text-gray-500">{{ __('aho.notifications.messages.none') }}</span>
                </x-filament::dropdown.list.item>
            @else
                @foreach ($latestMessages as $notification)
                    <x-filament::dropdown.list.item
                        tag="a"
                        :href="route('admin.notifications.show', ['country' => $country, 'notification' => $notification->getKey()])"
                        :spa-mode="false"
                    >
                        <div class="space-y-1 text-left" style="max-width: 24rem; white-space: normal; overflow-wrap: anywhere;">
                            <p class="text-sm font-medium text-gray-900">{{ $notification->data['title'] ?? __('aho.notifications.messages.item_title') }}</p>
                            <p class="text-sm text-gray-500">{{ \Illuminate\Support\Str::limit($notification->data['body'] ?? $notification->data['message'] ?? '', 80) }}</p>
                        </div>
                    </x-filament::dropdown.list.item>
                @endforeach
            @endif
        </x-filament::dropdown.list>

    </x-filament::dropdown>

    <x-filament::dropdown placement="bottom-end" width="md" teleport class="fi-topbar-item">
        <x-slot name="trigger">
            <button
                type="button"
                class="fi-topbar-item-btn"
                aria-label="{{ __('aho.notifications.system.aria_label') }}"
            >
                {{ \Filament\Support\generate_icon_html(\Filament\Support\Icons\Heroicon::OutlinedBellAlert, attributes: (new \Illuminate\View\ComponentAttributeBag)->class(['fi-topbar-item-icon'])) }}

                @if (max($unreadSystemNotifications, $pendingValidationCount))
                    <x-filament::badge size="sm" class="ml-2">{{ max($unreadSystemNotifications, $pendingValidationCount) }}</x-filament::badge>
                @endif
            </button>
        </x-slot>

        <x-filament::dropdown.list>
            <div class="fi-dropdown-header px-4 py-3 border-b border-gray-100">
                <p class="text-sm font-semibold text-gray-900">{{ __('aho.notifications.system.title') }}</p>
                <p class="mt-1 text-xs text-gray-500">{{ trans_choice('aho.notifications.system.summary', $pendingValidationCount, ['count' => $pendingValidationCount]) }}</p>
            </div>

            @if ($latestSystemNotifications->isEmpty())
                <x-filament::dropdown.list.item disabled>
                    <span class="text-sm text-gray-500">{{ __('aho.notifications.system.none') }}</span>
                </x-filament::dropdown.list.item>
            @else
                @foreach ($latestSystemNotifications as $notification)
                    <x-filament::dropdown.list.item
                        tag="a"
                        :href="route('admin.notifications.show', ['country' => $country, 'notification' => $notification->getKey()])"
                        :spa-mode="false"
                    >
                        <div class="space-y-1 text-left" style="max-width: 24rem; white-space: normal; overflow-wrap: anywhere;">
                            <p class="text-sm font-medium text-gray-900">{{ $notification->data['title'] ?? __('aho.notifications.system.item_title') }}</p>
                            <p class="text-sm text-gray-500">{{ \Illuminate\Support\Str::limit($notification->data['body'] ?? $notification->data['message'] ?? '', 80) }}</p>
                        </div>
                    </x-filament::dropdown.list.item>
                @endforeach
            @endif
        </x-filament::dropdown.list>

        <div class="border-t border-gray-100 px-4 py-3 text-right">
            <a href="{{ \App\Filament\Resources\HealthIndicatorValues\HealthIndicatorValueResource::getUrl('index', ['country' => $country]) }}" class="text-sm font-medium text-primary-600 hover:text-primary-700">{{ __('aho.notifications.system.view_pending') }}</a>
        </div>
    </x-filament::dropdown>
</div>

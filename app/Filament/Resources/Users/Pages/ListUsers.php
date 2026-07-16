<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Support\WarehouseUserSynchronizer;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Throwable;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    public function mount(): void
    {
        $user = auth()->user();
        $locationId = $user?->is_super_admin ? null : $user?->location_id;

        rescue(
            fn (): array => app(WarehouseUserSynchronizer::class)->syncIfDue($locationId),
            report: true,
        );

        parent::mount();
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('syncDjangoUsers')
                ->label(__('aho.actions.sync_django_users'))
                ->icon('heroicon-o-arrow-path')
                ->visible(fn (): bool => (bool) auth()->user()?->is_super_admin)
                ->action(function (): void {
                    try {
                        $summary = app(WarehouseUserSynchronizer::class)->sync();

                        Notification::make()
                            ->success()
                            ->title(__('aho.auth_management.sync.title'))
                            ->body(__('aho.auth_management.sync.body', $summary))
                            ->send();

                        $this->resetTable();
                    } catch (Throwable $exception) {
                        report($exception);

                        Notification::make()
                            ->danger()
                            ->title(__('aho.auth_management.sync.failed'))
                            ->send();
                    }
                }),
        ];
    }
}

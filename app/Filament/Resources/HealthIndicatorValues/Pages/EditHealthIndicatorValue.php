<?php

namespace App\Filament\Resources\HealthIndicatorValues\Pages;

use App\Models\HealthIndicatorValue;
use App\Filament\Resources\HealthIndicatorValues\HealthIndicatorValueResource;
use App\Services\DataQuality\DataQualityService;
use App\Support\ApprovalWorkflow;
use App\Support\UserPermissions;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditHealthIndicatorValue extends EditRecord
{
    protected static string $resource = HealthIndicatorValueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label(__('aho.actions.approve'))
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->getRecord() instanceof HealthIndicatorValue
                    && ! ApprovalWorkflow::isApproved($this->getRecord())
                    && (bool) auth()->user()
                    && UserPermissions::allowsResource(auth()->user(), HealthIndicatorValueResource::class, UserPermissions::ACTION_APPROVE))
                ->action(function (): void {
                    ApprovalWorkflow::approve($this->getRecord());
                }),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->validateQuality($data);

        return $data;
    }

    private function validateQuality(array $data): void
    {
        $issues = app(DataQualityService::class)->inspectIndicatorPayload($data);

        if ($issues === []) {
            return;
        }

        throw ValidationException::withMessages([
            'data.value_received' => collect($issues)->pluck('message')->implode(' '),
        ]);
    }
}

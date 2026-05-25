<?php

namespace App\Filament\Resources\HealthIndicatorValues\Pages;

use App\Filament\Resources\HealthIndicatorValues\HealthIndicatorValueResource;
use App\Filament\Resources\Pages\EditRecordAndReturnToList as EditRecord;
use App\Models\HealthIndicatorValue;
use App\Services\DataQuality\DataQualityService;
use App\Support\ApprovalWorkflow;
use App\Support\DataQuality\DqaIssueResolver;
use App\Support\UserCountryAccess;
use App\Support\UserPermissions;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Illuminate\Validation\ValidationException;

class EditHealthIndicatorValue extends EditRecord
{
    protected static string $resource = HealthIndicatorValueResource::class;

    /**
     * @var array<string, array<int, string>|string|null>|null
     */
    protected ?array $dqaPreviousSignature = null;

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
        /** @var HealthIndicatorValue $record */
        $record = $this->getRecord();
        $this->dqaPreviousSignature = DqaIssueResolver::signatureForValue($record);
        $data = UserCountryAccess::enforceLocationData($data);

        $this->validateQuality($data);

        return $data;
    }

    protected function afterSave(): void
    {
        /** @var HealthIndicatorValue $record */
        $record = $this->getRecord();

        DqaIssueResolver::deleteResolvedIssuesForValue($record, $this->dqaPreviousSignature);
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

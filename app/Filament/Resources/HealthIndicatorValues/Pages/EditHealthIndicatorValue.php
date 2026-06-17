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
        $data = $this->normalizeIndicatorPayload($data);
        $this->validatePriorityLimit($data, $record);

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

    /**
     * Keep the database period column in sync while hiding it from the form.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeIndicatorPayload(array $data): array
    {
        $start = $data['start_period'] ?? null;
        $end = $data['end_period'] ?? null;
        $data['period'] = filled($start) && filled($end) && (string) $start !== (string) $end
            ? "{$start}-{$end}"
            : (string) ($start ?? $end ?? '');

        if (filled($data['value_received'] ?? null)) {
            $data['string_value'] = null;
        } elseif (filled($data['string_value'] ?? null)) {
            $data['value_received'] = null;
        } else {
            throw ValidationException::withMessages([
                'data.value_received' => __('aho.indicator_values.value_required'),
            ]);
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function validatePriorityLimit(array $data, HealthIndicatorValue $record): void
    {
        if (! (bool) ($data['priority'] ?? false) || blank($data['location_id'] ?? null)) {
            return;
        }

        $priorityCount = HealthIndicatorValue::query()
            ->where('location_id', $data['location_id'])
            ->where('priority', true)
            ->whereKeyNot($record->getKey())
            ->count();

        if ($priorityCount >= 10 && ! (bool) $record->priority) {
            throw ValidationException::withMessages([
                'data.priority' => __('aho.indicator_values.priority_limit_reached'),
            ]);
        }
    }
}

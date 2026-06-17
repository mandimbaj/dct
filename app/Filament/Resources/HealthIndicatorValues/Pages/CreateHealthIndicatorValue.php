<?php

namespace App\Filament\Resources\HealthIndicatorValues\Pages;

use App\Filament\Resources\HealthIndicatorValues\HealthIndicatorValueResource;
use App\Models\HealthIndicatorValue;
use App\Services\DataQuality\DataQualityService;
use App\Support\ApprovalWorkflow;
use App\Support\UserCountryAccess;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateHealthIndicatorValue extends CreateRecord
{
    protected static string $resource = HealthIndicatorValueResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = UserCountryAccess::enforceLocationData($data);
        $data = $this->normalizeIndicatorPayload($data);
        $this->validatePriorityLimit($data);
        $data[ApprovalWorkflow::STATUS_COLUMN] = ApprovalWorkflow::STATUS_PENDING;
        $data[ApprovalWorkflow::MIRROR_COLUMN] = ApprovalWorkflow::STATUS_PENDING;
        $data['approved_by'] = null;
        $data['approved_at'] = null;

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
    private function validatePriorityLimit(array $data): void
    {
        if (! (bool) ($data['priority'] ?? false) || blank($data['location_id'] ?? null)) {
            return;
        }

        $priorityCount = HealthIndicatorValue::query()
            ->where('location_id', $data['location_id'])
            ->where('priority', true)
            ->count();

        if ($priorityCount >= 10) {
            throw ValidationException::withMessages([
                'data.priority' => __('aho.indicator_values.priority_limit_reached'),
            ]);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

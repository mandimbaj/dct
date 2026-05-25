<?php

namespace App\Filament\Resources\HealthIndicatorValues\Pages;

use App\Filament\Resources\HealthIndicatorValues\HealthIndicatorValueResource;
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

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

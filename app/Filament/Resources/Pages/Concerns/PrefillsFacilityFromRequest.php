<?php

namespace App\Filament\Resources\Pages\Concerns;

trait PrefillsFacilityFromRequest
{
    protected function afterFill(): void
    {
        $facilityId = request()->integer('facility_id');

        if ($facilityId <= 0) {
            return;
        }

        $this->data['facility_id'] = $facilityId;
    }
}

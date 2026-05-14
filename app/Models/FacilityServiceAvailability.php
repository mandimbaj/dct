<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacilityServiceAvailability extends Model
{
    protected $connection = 'warehouse';

    protected $table = 'stg_facility_services_availability';

    protected $primaryKey = 'availability_id';

    protected $guarded = [];

    public const CREATED_AT = 'date_created';

    public const UPDATED_AT = 'date_lastupdated';

    protected function casts(): array
    {
        return [
            'provided' => 'boolean',
            'specialunit' => 'boolean',
            'staff' => 'boolean',
            'infrastructure' => 'boolean',
            'supplies' => 'boolean',
            'date_assessed' => 'date',
            'date_created' => 'datetime',
            'date_lastupdated' => 'datetime',
        ];
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(HealthFacility::class, 'facility_id', 'facility_id');
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(FacilityServiceDomain::class, 'domain_id', 'domain_id');
    }

    public function intervention(): BelongsTo
    {
        return $this->belongsTo(FacilityServiceIntervention::class, 'intervention_id', 'intervention_id');
    }

    public function serviceArea(): BelongsTo
    {
        return $this->belongsTo(FacilityServiceArea::class, 'service_id', 'area_id');
    }

    public function getDisplayNameAttribute(): string
    {
        return implode(' / ', array_filter([
            $this->facility?->display_name,
            $this->domain?->display_name,
            $this->assessedDateLabel(),
        ])) ?: (string) $this->getKey();
    }

    private function assessedDateLabel(): ?string
    {
        return $this->date_assessed instanceof CarbonInterface
            ? $this->date_assessed->toDateString()
            : ($this->date_assessed ? (string) $this->date_assessed : null);
    }
}

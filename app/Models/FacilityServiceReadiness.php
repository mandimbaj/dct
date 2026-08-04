<?php

namespace App\Models;

use App\Support\GeneratedCode;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacilityServiceReadiness extends Model
{
    protected $connection = 'warehouse';

    protected $table = 'stg_facility_services_readiness';

    protected $primaryKey = 'readiness_id';

    protected $guarded = [];

    public const CREATED_AT = 'date_created';

    public const UPDATED_AT = 'date_lastupdated';

    protected function casts(): array
    {
        return [
            'available' => 'integer',
            'require' => 'integer',
            'date_assessed' => 'date',
            'date_created' => 'datetime',
            'date_lastupdated' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (FacilityServiceReadiness $readiness): void {
            GeneratedCode::ensureUuid($readiness);
            GeneratedCode::ensure($readiness, 'code', 'FSR', 45);
            $readiness->user_id ??= auth()->id() ?? 1;
        });
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(HealthFacility::class, 'facility_id', 'facility_id');
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(FacilityServiceDomain::class, 'domain_id', 'domain_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(FacilityProvisionUnit::class, 'units_id', 'infra_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function warehouseUploadedBy(): BelongsTo
    {
        return $this->belongsTo(WarehouseAuthenticationUser::class, 'user_id', 'id');
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

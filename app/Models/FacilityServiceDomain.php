<?php

namespace App\Models;

use App\Models\Concerns\HasPreferredTranslationName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FacilityServiceDomain extends Model
{
    use HasPreferredTranslationName;

    protected $connection = 'warehouse';

    protected $table = 'stg_facility_services';

    protected $primaryKey = 'domain_id';

    protected $guarded = [];

    public const CREATED_AT = 'date_created';

    public const UPDATED_AT = 'date_lastupdated';

    protected function casts(): array
    {
        return [
            'category' => 'integer',
            'date_created' => 'datetime',
            'date_lastupdated' => 'datetime',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id', 'domain_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id', 'domain_id');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(FacilityServiceDomainTranslation::class, 'master_id', 'domain_id');
    }

    public function provisionUnits(): HasMany
    {
        return $this->hasMany(FacilityProvisionUnit::class, 'domain_id', 'domain_id');
    }

    public function interventions(): HasMany
    {
        return $this->hasMany(FacilityServiceIntervention::class, 'domain_id', 'domain_id');
    }

    public function serviceAvailabilities(): HasMany
    {
        return $this->hasMany(FacilityServiceAvailability::class, 'domain_id', 'domain_id');
    }

    public function serviceCapacities(): HasMany
    {
        return $this->hasMany(FacilityServiceCapacity::class, 'domain_id', 'domain_id');
    }

    public function serviceReadiness(): HasMany
    {
        return $this->hasMany(FacilityServiceReadiness::class, 'domain_id', 'domain_id');
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->preferredTranslationName($this->code);
    }

    public function getCategoryLabelAttribute(): string
    {
        return match ((int) $this->category) {
            1 => __('aho.facility_service_categories.availability'),
            2 => __('aho.facility_service_categories.capacity'),
            3 => __('aho.facility_service_categories.readiness'),
            default => (string) ($this->category ?? ''),
        };
    }
}

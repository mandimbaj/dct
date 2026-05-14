<?php

namespace App\Models;

use App\Models\Concerns\HasPreferredTranslationName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FacilityProvisionUnit extends Model
{
    use HasPreferredTranslationName;

    protected $connection = 'warehouse';

    protected $table = 'stg_facility_service_units';

    protected $primaryKey = 'infra_id';

    protected $guarded = [];

    public const CREATED_AT = 'date_created';

    public const UPDATED_AT = 'date_lastupdated';

    protected function casts(): array
    {
        return [
            'date_created' => 'datetime',
            'date_lastupdated' => 'datetime',
        ];
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(FacilityServiceDomain::class, 'domain_id', 'domain_id');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(FacilityProvisionUnitTranslation::class, 'master_id', 'infra_id');
    }

    public function serviceCapacities(): HasMany
    {
        return $this->hasMany(FacilityServiceCapacity::class, 'units_id', 'infra_id');
    }

    public function serviceReadiness(): HasMany
    {
        return $this->hasMany(FacilityServiceReadiness::class, 'units_id', 'infra_id');
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->preferredTranslationName($this->code);
    }
}

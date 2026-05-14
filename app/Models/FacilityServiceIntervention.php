<?php

namespace App\Models;

use App\Models\Concerns\HasPreferredTranslationName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FacilityServiceIntervention extends Model
{
    use HasPreferredTranslationName;

    protected $connection = 'warehouse';

    protected $table = 'stg_facility_service_intervention';

    protected $primaryKey = 'intervention_id';

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
        return $this->hasMany(FacilityServiceInterventionTranslation::class, 'master_id', 'intervention_id');
    }

    public function serviceAreas(): HasMany
    {
        return $this->hasMany(FacilityServiceArea::class, 'intervention_id', 'intervention_id');
    }

    public function serviceAvailabilities(): HasMany
    {
        return $this->hasMany(FacilityServiceAvailability::class, 'intervention_id', 'intervention_id');
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->preferredTranslationName($this->code);
    }
}

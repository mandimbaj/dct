<?php

namespace App\Models;

use App\Models\Concerns\HasPreferredTranslationName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FacilityServiceArea extends Model
{
    use HasPreferredTranslationName;

    protected $connection = 'warehouse';

    protected $table = 'stg_facility_service_area';

    protected $primaryKey = 'area_id';

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

    public function intervention(): BelongsTo
    {
        return $this->belongsTo(FacilityServiceIntervention::class, 'intervention_id', 'intervention_id');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(FacilityServiceAreaTranslation::class, 'master_id', 'area_id');
    }

    public function serviceAvailabilities(): HasMany
    {
        return $this->hasMany(FacilityServiceAvailability::class, 'service_id', 'area_id');
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->preferredTranslationName($this->code);
    }
}

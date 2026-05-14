<?php

namespace App\Models;

use App\Models\Concerns\HasPreferredTranslationName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FacilityType extends Model
{
    use HasPreferredTranslationName;

    protected $connection = 'warehouse';

    protected $table = 'stg_facility_type';

    protected $primaryKey = 'type_id';

    protected $guarded = [];

    public const CREATED_AT = 'date_created';

    public const UPDATED_AT = 'date_lastupdated';

    public function translations(): HasMany
    {
        return $this->hasMany(FacilityTypeTranslation::class, 'master_id', 'type_id');
    }

    public function facilities(): HasMany
    {
        return $this->hasMany(HealthFacility::class, 'type_id', 'type_id');
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->preferredTranslationName($this->code);
    }
}

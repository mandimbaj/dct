<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacilityTypeTranslation extends Model
{
    protected $connection = 'warehouse';

    protected $table = 'stg_facility_type_translation';

    public $timestamps = false;

    public function facilityType(): BelongsTo
    {
        return $this->belongsTo(FacilityType::class, 'master_id', 'type_id');
    }
}

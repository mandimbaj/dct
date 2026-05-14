<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacilityServiceAreaTranslation extends Model
{
    protected $connection = 'warehouse';

    protected $table = 'stg_facility_service_area_translation';

    public $timestamps = false;

    protected $guarded = [];

    public function serviceArea(): BelongsTo
    {
        return $this->belongsTo(FacilityServiceArea::class, 'master_id', 'area_id');
    }
}

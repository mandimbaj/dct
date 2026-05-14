<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacilityServiceInterventionTranslation extends Model
{
    protected $connection = 'warehouse';

    protected $table = 'stg_facility_service_intervention_translation';

    public $timestamps = false;

    protected $guarded = [];

    public function intervention(): BelongsTo
    {
        return $this->belongsTo(FacilityServiceIntervention::class, 'master_id', 'intervention_id');
    }
}

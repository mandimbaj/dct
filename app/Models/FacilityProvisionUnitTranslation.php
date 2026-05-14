<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacilityProvisionUnitTranslation extends Model
{
    protected $connection = 'warehouse';

    protected $table = 'stg_facility_service_units_translation';

    public $timestamps = false;

    protected $guarded = [];

    public function provisionUnit(): BelongsTo
    {
        return $this->belongsTo(FacilityProvisionUnit::class, 'master_id', 'infra_id');
    }
}

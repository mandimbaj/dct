<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacilityServiceDomainTranslation extends Model
{
    protected $connection = 'warehouse';

    protected $table = 'stg_facility_services_translation';

    public $timestamps = false;

    protected $guarded = [];

    public function domain(): BelongsTo
    {
        return $this->belongsTo(FacilityServiceDomain::class, 'master_id', 'domain_id');
    }
}

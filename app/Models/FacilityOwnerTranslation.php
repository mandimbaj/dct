<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacilityOwnerTranslation extends Model
{
    protected $connection = 'warehouse';

    protected $table = 'stg_facility_owner_translation';

    public $timestamps = false;

    public function facilityOwner(): BelongsTo
    {
        return $this->belongsTo(FacilityOwner::class, 'master_id', 'owner_id');
    }
}

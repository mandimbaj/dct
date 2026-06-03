<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EconomicZoneTranslation extends Model
{
    protected $connection = 'warehouse';

    protected $table = 'stg_economic_zones_translation';

    protected $guarded = [];

    public $timestamps = false;

    public function economicZone(): BelongsTo
    {
        return $this->belongsTo(EconomicZone::class, 'master_id', 'economiczone_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HealthCadreTranslation extends Model
{
    protected $connection = 'warehouse';

    protected $table = 'stg_health_cadre_translation';

    public $timestamps = false;

    protected $guarded = [];

    public function cadre(): BelongsTo
    {
        return $this->belongsTo(HealthCadre::class, 'master_id', 'cadre_id');
    }
}

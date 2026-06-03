<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HealthServiceProgrammeTranslation extends Model
{
    protected $connection = 'warehouse';

    protected $table = 'stg_health_continuity_programs_translation';

    protected $guarded = [];

    public $timestamps = false;

    public function programme(): BelongsTo
    {
        return $this->belongsTo(HealthServiceProgramme::class, 'master_id', 'domain_id');
    }
}

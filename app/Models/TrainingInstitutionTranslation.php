<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingInstitutionTranslation extends Model
{
    protected $connection = 'warehouse';

    protected $table = 'stg_traininginstitution_translation';

    public $timestamps = false;

    protected $guarded = [];

    public function institution(): BelongsTo
    {
        return $this->belongsTo(TrainingInstitution::class, 'master_id', 'institution_id');
    }
}

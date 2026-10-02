<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['language_code', 'name', 'shortname', 'description', 'master_id'])]
class TimePeriodTranslation extends Model
{
    protected $connection = 'warehouse';

    protected $table = 'stg_periodicity_type_translation';

    public $timestamps = false;

    protected $guarded = [];

    public function timePeriod(): BelongsTo
    {
        return $this->belongsTo(TimePeriod::class, 'master_id', 'period_id');
    }
}

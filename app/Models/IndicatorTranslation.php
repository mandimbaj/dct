<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'language_code',
    'name',
    'shortname',
    'definition',
    'preferred_datasources',
    'numerator_description',
    'denominator_description',
    'master_id',
])]
class IndicatorTranslation extends Model
{
    protected $connection = 'warehouse';

    protected $table = 'stg_indicator_translation';

    public $timestamps = false;

    public function indicator(): BelongsTo
    {
        return $this->belongsTo(Indicator::class, 'master_id', 'indicator_id');
    }
}

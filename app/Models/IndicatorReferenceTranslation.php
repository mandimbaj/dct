<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['language_code', 'name', 'shortname', 'description', 'master_id'])]
class IndicatorReferenceTranslation extends Model
{
    protected $connection = 'warehouse';

    protected $table = 'stg_indicator_reference_translation';

    public $timestamps = false;

    protected $guarded = [];

    public function reference(): BelongsTo
    {
        return $this->belongsTo(IndicatorReference::class, 'master_id', 'reference_id');
    }
}

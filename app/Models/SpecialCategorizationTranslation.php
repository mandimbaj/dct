<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpecialCategorizationTranslation extends Model
{
    protected $connection = 'warehouse';

    protected $table = 'stg_specialcategorization_translation';

    protected $guarded = [];

    public $timestamps = false;

    public function specialCategorization(): BelongsTo
    {
        return $this->belongsTo(SpecialCategorization::class, 'master_id', 'specialstates_id');
    }
}

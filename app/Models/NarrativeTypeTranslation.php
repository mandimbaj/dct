<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NarrativeTypeTranslation extends Model
{
    protected $connection = 'warehouse';

    protected $table = 'stg_narrative_type_translation';

    protected $guarded = [];

    public $timestamps = false;

    public function narrativeType(): BelongsTo
    {
        return $this->belongsTo(NarrativeType::class, 'master_id', 'type_id');
    }
}

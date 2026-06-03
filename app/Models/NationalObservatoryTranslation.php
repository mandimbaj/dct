<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NationalObservatoryTranslation extends Model
{
    protected $connection = 'warehouse';

    protected $table = 'stg_national_observatory_translation';

    protected $guarded = [];

    public $timestamps = false;

    public function nationalObservatory(): BelongsTo
    {
        return $this->belongsTo(NationalObservatory::class, 'master_id', 'observatory_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UhcClockIndicatorGroupTranslation extends Model
{
    protected $connection = 'warehouse';

    protected $table = 'stg_uhclock_indicator_groups_translation';

    public $timestamps = false;

    protected $guarded = [];

    public function group(): BelongsTo
    {
        return $this->belongsTo(UhcClockIndicatorGroup::class, 'master_id', 'group_id');
    }
}

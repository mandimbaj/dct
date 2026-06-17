<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class UhcClockIndicator extends Model
{
    protected $connection = 'warehouse';

    protected $table = 'stg_uhclock_indicators';

    protected $primaryKey = 'id';

    protected $guarded = [];

    public $timestamps = false;

    public function group(): BelongsTo
    {
        return $this->belongsTo(UhcClockIndicatorGroup::class, 'group_id', 'group_id');
    }

    public function indicator(): BelongsTo
    {
        return $this->belongsTo(Indicator::class, 'indicator_id', 'indicator_id');
    }

    public function themes(): BelongsToMany
    {
        return $this->belongsToMany(
            UhcClockTheme::class,
            'stg_uhclock_indicator_themes_indicators',
            'stguhclockindicators_id',
            'stguhcindicatortheme_id',
            'id',
            'domain_id',
        );
    }
}

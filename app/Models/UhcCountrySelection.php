<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class UhcCountrySelection extends Model
{
    protected $connection = 'warehouse';

    protected $table = 'stg_uhclock_country_indicators_selection';

    protected $primaryKey = 'countrychoice_id';

    protected $guarded = [];

    public $timestamps = false;

    public function location(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'location_id', 'location_id');
    }

    public function themes(): BelongsToMany
    {
        return $this->belongsToMany(
            UhcClockTheme::class,
            'stg_uhclock_country_indicators_selection_domain',
            'countryselectionuhcindicators_id',
            'stguhcindicatortheme_id',
            'countrychoice_id',
            'domain_id',
        );
    }

    public function indicators(): BelongsToMany
    {
        return $this->belongsToMany(
            UhcClockIndicator::class,
            'stg_uhclock_country_indicators_selection_indicators',
            'countryselectionuhcindicators_id',
            'stguhclockindicators_id',
            'countrychoice_id',
            'id',
        );
    }
}

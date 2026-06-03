<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalyticsNarrative extends Model
{
    protected $connection = 'warehouse';

    protected $table = 'stg_analytics_narrative';

    protected $primaryKey = 'analyticstext_id';

    protected $guarded = [];

    public const CREATED_AT = 'date_created';

    public const UPDATED_AT = 'date_lastupdated';

    protected function casts(): array
    {
        return [
            'date_created' => 'datetime',
            'date_lastupdated' => 'datetime',
        ];
    }

    public function narrativeType(): BelongsTo
    {
        return $this->belongsTo(NarrativeType::class, 'narrative_type_id', 'type_id');
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(IndicatorDomain::class, 'domain_id', 'domain_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'location_id', 'location_id');
    }
}

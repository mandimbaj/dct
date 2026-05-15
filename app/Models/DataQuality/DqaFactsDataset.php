<?php

namespace App\Models\DataQuality;

use App\Models\Country;
use App\Models\DataSource;
use App\Models\Indicator;
use App\Models\IndicatorCategory;
use App\Models\MeasureMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DqaFactsDataset extends Model
{
    protected $connection = 'warehouse';

    protected $table = 'fact_data_indicators';

    protected $primaryKey = 'fact_id';

    public const CREATED_AT = 'date_created';

    public const UPDATED_AT = 'date_lastupdated';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'numerator_value' => 'decimal:2',
            'denominator_value' => 'decimal:2',
            'value_received' => 'decimal:2',
            'min_value' => 'decimal:2',
            'max_value' => 'decimal:2',
            'target_value' => 'decimal:2',
            'priority' => 'boolean',
            'approved_at' => 'datetime',
            'date_created' => 'datetime',
            'date_lastupdated' => 'datetime',
        ];
    }

    public function indicator(): BelongsTo
    {
        return $this->belongsTo(Indicator::class, 'indicator_id', 'indicator_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'location_id', 'location_id');
    }

    public function categoryOption(): BelongsTo
    {
        return $this->belongsTo(IndicatorCategory::class, 'categoryoption_id', 'categoryoption_id');
    }

    public function dataSource(): BelongsTo
    {
        return $this->belongsTo(DataSource::class, 'datasource_id', 'datasource_id');
    }

    public function measureMethod(): BelongsTo
    {
        return $this->belongsTo(MeasureMethod::class, 'measuremethod_id', 'measuremethod_id');
    }
}

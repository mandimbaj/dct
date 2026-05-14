<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataElementValue extends Model
{
    protected $connection = 'warehouse';

    protected $table = 'fact_data_element';

    protected $primaryKey = 'fact_id';

    protected $guarded = [];

    public const CREATED_AT = 'date_created';

    public const UPDATED_AT = 'date_lastupdated';

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'target_value' => 'decimal:2',
            'date_created' => 'datetime',
            'date_lastupdated' => 'datetime',
        ];
    }

    public function dataElement(): BelongsTo
    {
        return $this->belongsTo(DataElement::class, 'dataelement_id', 'dataelement_id');
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
}

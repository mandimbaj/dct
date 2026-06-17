<?php

namespace App\Models;

use App\Support\ApprovalWorkflow;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class HealthWorkforceValue extends Model
{
    protected $connection = 'warehouse';

    protected $table = 'fact_health_workforce';

    protected $primaryKey = 'fact_id';

    protected $guarded = [];

    public const CREATED_AT = 'date_created';

    public const UPDATED_AT = 'date_lastupdated';

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'date_created' => 'datetime',
            'date_lastupdated' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (HealthWorkforceValue $value): void {
            $value->uuid ??= (string) Str::uuid();
            $value->user_id ??= auth()->id() ?? 1;
            $value->status ??= ApprovalWorkflow::STATUS_PENDING;
        });

        static::saving(function (HealthWorkforceValue $value): void {
            if (filled($value->start_year) && filled($value->end_year)) {
                $value->period = ((int) $value->start_year === (int) $value->end_year)
                    ? (string) (int) $value->start_year
                    : ((int) $value->start_year).'-'.((int) $value->end_year);
            }
        });
    }

    public function cadre(): BelongsTo
    {
        return $this->belongsTo(HealthCadre::class, 'cadre_id', 'cadre_id');
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

<?php

namespace App\Models;

use App\Support\ApprovalWorkflow;
use App\Support\GeneratedCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Throwable;

class HealthServiceValue extends Model
{
    protected $connection = 'warehouse';

    protected $table = 'fact_health_services';

    protected $primaryKey = 'fact_id';

    protected $guarded = [];

    public const CREATED_AT = 'date_created';

    public const UPDATED_AT = 'date_lastupdated';

    protected function casts(): array
    {
        return [
            'value_received' => 'decimal:2',
            'value_calculated' => 'decimal:2',
            'start_period' => 'date',
            'end_period' => 'date',
            'date_created' => 'datetime',
            'date_lastupdated' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (HealthServiceValue $value): void {
            GeneratedCode::ensureUuid($value);
            $value->user_id ??= auth()->id() ?? 1;
            $value->comment ??= ApprovalWorkflow::STATUS_PENDING;
        });

        static::saving(function (HealthServiceValue $value): void {
            $value->period = self::periodLabel($value);
        });
    }

    private static function periodLabel(HealthServiceValue $value): ?string
    {
        if (blank($value->start_period)) {
            return $value->period;
        }

        try {
            $start = Carbon::parse($value->start_period);
            $end = filled($value->end_period) ? Carbon::parse($value->end_period) : $start;
        } catch (Throwable) {
            return $value->period;
        }

        return match ((int) $value->periodicity_id) {
            1 => $start->format('Y-m'),
            2 => $start->format('Y').'-Q'.(int) ceil($start->month / 3),
            3 => $start->format('Y').($end->month < 7 ? '-S1' : '-S2'),
            4 => $start->format('Y'),
            default => $start->year === $end->year
                ? (string) $start->year
                : $start->year.'-'.$end->year,
        };
    }

    public function indicator(): BelongsTo
    {
        return $this->belongsTo(Indicator::class, 'indicator_id', 'indicator_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'location_id', 'location_id');
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(TimePeriod::class, 'periodicity_id', 'period_id');
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

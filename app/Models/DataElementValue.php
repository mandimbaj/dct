<?php

namespace App\Models;

use App\Support\ApprovalWorkflow;
use App\Support\GeneratedCode;
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
            'value' => 'decimal:3',
            'target_value' => 'decimal:3',
            'date_created' => 'datetime',
            'date_lastupdated' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (DataElementValue $value): void {
            GeneratedCode::ensureUuid($value);
            $value->user_id ??= auth()->id();
            $value->comment ??= ApprovalWorkflow::STATUS_PENDING;
            $value->period = static::periodFromYears($value->start_year, $value->end_year);
        });

        static::saving(function (DataElementValue $value): void {
            $value->comment = ApprovalWorkflow::normalizeStatus($value->comment);
            $value->period = static::periodFromYears($value->start_year, $value->end_year);
        });
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

    public function valueType(): BelongsTo
    {
        return $this->belongsTo(ValueDataType::class, 'valuetype_id', 'valuetype_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function warehouseUploadedBy(): BelongsTo
    {
        return $this->belongsTo(WarehouseAuthenticationUser::class, 'user_id', 'id');
    }

    private static function periodFromYears(mixed $startYear, mixed $endYear): string
    {
        if (blank($startYear) || blank($endYear)) {
            return '';
        }

        $startYear = (int) $startYear;
        $endYear = (int) $endYear;

        return $startYear === $endYear ? (string) $startYear : "{$startYear}-{$endYear}";
    }
}

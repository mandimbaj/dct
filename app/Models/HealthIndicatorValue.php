<?php

namespace App\Models;

use App\Notifications\SystemNotification;
use App\Support\ApprovalWorkflow;
use App\Support\NotificationRecipients;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

#[Fillable([
    'uuid',
    'indicator_id',
    'location_id',
    'start_period',
    'end_period',
    'period',
    'categoryoption_id',
    'datasource_id',
    'measuremethod_id',
    'numerator_value',
    'denominator_value',
    'value_received',
    'min_value',
    'max_value',
    'target_value',
    'string_value',
    'comment',
    'priority',
    'user_id',
    'approval_status',
    'approved_by',
    'approved_at',
])]
class HealthIndicatorValue extends Model
{
    use HasFactory;

    protected $connection = 'warehouse';

    protected $table = 'fact_data_indicators';

    protected $primaryKey = 'fact_id';

    public const CREATED_AT = 'date_created';

    public const UPDATED_AT = 'date_lastupdated';

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

    protected static function booted(): void
    {
        static::creating(function (HealthIndicatorValue $value): void {
            $value->uuid ??= (string) Str::uuid();
            $value->priority ??= false;
            $value->user_id ??= auth()->id() ?? 1;
            ApprovalWorkflow::syncStatus($value, $value->comment);
        });

        static::updating(function (HealthIndicatorValue $value): void {
            if ($value->isDirty(['comment', 'approval_status', 'approved_by', 'approved_at'])) {
                ApprovalWorkflow::syncStatus($value, $value->comment);

                return;
            }

            if ($value->isDirty()) {
                ApprovalWorkflow::markPending($value);
            }
        });

        static::created(function (HealthIndicatorValue $value): void {
            $value->notifyPendingValidation();
        });

        static::updated(function (HealthIndicatorValue $value): void {
            if (
                $value->wasChanged([ApprovalWorkflow::STATUS_COLUMN, ApprovalWorkflow::MIRROR_COLUMN])
                && ApprovalWorkflow::status($value) === ApprovalWorkflow::STATUS_PENDING
            ) {
                $value->notifyPendingValidation();
            }
        });
    }

    private function notifyPendingValidation(): void
    {
        $countryCode = optional($this->location)->iso_alpha;
        $title = __('aho.notifications.system.created_indicator_title');
        $body = __('aho.notifications.system.created_indicator_body', [
            'indicator' => optional($this->indicator)->display_name ?? __('aho.notifications.system.indicator_unknown'),
            'country' => $countryCode ? strtoupper($countryCode) : __('aho.notifications.system.country_unknown'),
        ]);

        Notification::send(
            NotificationRecipients::forCountry($this->location_id, auth()->id()),
            new SystemNotification($title, $body, $countryCode),
        );
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

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}

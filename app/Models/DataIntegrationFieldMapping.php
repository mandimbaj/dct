<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataIntegrationFieldMapping extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'transformation_config' => 'array',
        ];
    }

    public function connection(): BelongsTo
    {
        return $this->belongsTo(DataIntegrationConnection::class, 'data_integration_connection_id');
    }

    /**
     * Field type options for mapping configuration
     */
    public static function fieldTypeOptions(): array
    {
        return [
            'direct' => __('aho.data_integration.field_types.direct'),
            'computed' => __('aho.data_integration.field_types.computed'),
            'conditional' => __('aho.data_integration.field_types.conditional'),
            'skip' => __('aho.data_integration.field_types.skip'),
        ];
    }

    /**
     * Get all local field options (from our system)
     */
    public static function localFieldOptions(): array
    {
        return [
            'location_id' => __('aho.fields.location').' (location_id)',
            'indicator_id' => __('aho.fields.indicator').' (indicator_id)',
            'start_period' => __('aho.fields.start').' (start_period)',
            'end_period' => __('aho.fields.end').' (end_period)',
            'period' => __('aho.fields.period').' (period)',
            'categoryoption_id' => __('aho.data_integration.fields.age_sex_category').' (categoryoption_id)',
            'datasource_id' => __('aho.fields.source').' (datasource_id)',
            'measuremethod_id' => __('aho.fields.measure_method').' (measuremethod_id)',
            'value_received' => __('aho.fields.value_received').' (value_received)',
            'numerator_value' => __('aho.fields.numerator').' (numerator_value)',
            'denominator_value' => __('aho.fields.denominator').' (denominator_value)',
            'min_value' => __('aho.fields.min').' (min_value)',
            'max_value' => __('aho.fields.max').' (max_value)',
            'target_value' => __('aho.fields.target').' (target_value)',
            'string_value' => __('aho.fields.text_value').' (string_value)',
            'comment' => __('aho.fields.approval_status').' (comment)',
            'priority' => __('aho.fields.priority').' (priority)',
        ];
    }
}

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
            'country_id' => 'Pays (Country)',
            'indicator_id' => 'Indicateur (Indicator)',
            'year' => 'Année (Year)',
            'period' => 'Période (Period)',
            'sex' => 'Sexe (Sex)',
            'age_group' => 'Groupe d\'âge (Age Group)',
            'value' => 'Valeur (Value)',
            'unit_override' => 'Unité (Unit)',
            'lower_bound' => 'Limite inférieure (Lower Bound)',
            'upper_bound' => 'Limite supérieure (Upper Bound)',
            'comment' => 'Commentaire (Comment)',
            'data_source_id' => 'Source de données (Data Source)',
        ];
    }
}

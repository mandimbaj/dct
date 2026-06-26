<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class DataIntegrationFieldMapping extends Model
{
    /** @var array<int, string> */
    private const REFERENCE_FIELDS = [
        'location_id',
        'indicator_id',
        'categoryoption_id',
        'datasource_id',
        'measuremethod_id',
    ];

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
            'lookup' => __('aho.data_integration.field_types.lookup'),
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
            'location_id' => __('aho.data_integration.mapping_targets.location'),
            'indicator_id' => __('aho.data_integration.mapping_targets.indicator'),
            'start_period' => __('aho.fields.start').' (start_period)',
            'end_period' => __('aho.fields.end').' (end_period)',
            'period' => __('aho.fields.period').' (period)',
            'categoryoption_id' => __('aho.data_integration.mapping_targets.category_option'),
            'datasource_id' => __('aho.data_integration.mapping_targets.data_source'),
            'measuremethod_id' => __('aho.data_integration.mapping_targets.measure_method'),
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

    /** @return array<string, string> */
    public static function referenceMatchOptions(): array
    {
        return [
            'auto' => __('aho.data_integration.reference_matches.auto'),
            'code' => __('aho.data_integration.reference_matches.code'),
            'name' => __('aho.data_integration.reference_matches.name'),
            'id' => __('aho.data_integration.reference_matches.id'),
        ];
    }

    public static function isReferenceField(?string $field): bool
    {
        return in_array($field, self::REFERENCE_FIELDS, true);
    }

    /**
     * Suggest source-to-target mappings without replacing the target's internal foreign keys.
     *
     * @param  array<int, string>  $externalFields
     * @return array<int, array<string, mixed>>
     */
    public static function suggestMappings(array $externalFields): array
    {
        $aliases = [
            'location_id' => ['countrycode', 'locationcode', 'orgunitcode', 'isoalpha', 'iso2', 'iso3', 'locationid', 'orgunitid', 'country', 'countryname', 'location', 'locationname', 'orgunit', 'orgunitname'],
            'indicator_id' => ['indicatorcode', 'afrocode', 'indicatorid', 'dataelementcode', 'dataelementid', 'indicator', 'indicatorname', 'dataelement', 'dataelementname'],
            'categoryoption_id' => ['categoryoptioncode', 'categoryoptionid', 'categoryoption', 'categoryoptionname', 'disaggregation', 'disaggregationoption'],
            'datasource_id' => ['datasourcecode', 'sourcecode', 'datasourceid', 'sourceid', 'datasource', 'datasourcename', 'source', 'sourcename'],
            'measuremethod_id' => ['measuremethodcode', 'measuretypecode', 'measuremethodid', 'measuretypeid', 'measuremethod', 'measuremethodname', 'measuretype', 'measuretypename'],
            'start_period' => ['startperiod', 'startyear', 'yearstart'],
            'end_period' => ['endperiod', 'endyear', 'yearend'],
            'period' => ['period', 'year', 'dateperiod'],
            'value_received' => ['valuereceived', 'indicatorvalue', 'numericvalue', 'value'],
            'numerator_value' => ['numeratorvalue', 'numerator'],
            'denominator_value' => ['denominatorvalue', 'denominator'],
            'min_value' => ['minvalue', 'minimumvalue', 'minimum'],
            'max_value' => ['maxvalue', 'maximumvalue', 'maximum'],
            'target_value' => ['targetvalue', 'target'],
            'string_value' => ['stringvalue', 'textvalue', 'text'],
            'comment' => ['comment', 'comments', 'remark', 'remarks', 'status'],
            'priority' => ['priority'],
        ];

        $normalizedFields = collect($externalFields)
            ->mapWithKeys(fn (string $field): array => [self::normalizeIdentifier($field) => $field]);
        $used = [];
        $mappings = [];

        foreach ($aliases as $localField => $candidates) {
            $externalField = collect($candidates)
                ->map(fn (string $candidate): ?string => $normalizedFields->get($candidate))
                ->first(fn (?string $field): bool => filled($field) && ! in_array($field, $used, true));

            if (blank($externalField)) {
                continue;
            }

            $used[] = $externalField;
            $isReference = self::isReferenceField($localField);

            $mappings[] = [
                'local_field' => $localField,
                'external_field' => $externalField,
                'field_type' => $isReference ? 'lookup' : 'direct',
                'reference_match' => $isReference ? self::inferReferenceMatch($externalField) : null,
                'is_required' => in_array($localField, ['location_id', 'indicator_id', 'period', 'value_received'], true),
                'default_value' => null,
                'transformation_rule' => null,
                'notes' => null,
            ];
        }

        return $mappings;
    }

    private static function inferReferenceMatch(string $externalField): string
    {
        $field = self::normalizeIdentifier($externalField);

        if (str_contains($field, 'code') || str_contains($field, 'afro') || str_starts_with($field, 'iso')) {
            return 'code';
        }

        return str_ends_with($field, 'id') ? 'id' : 'name';
    }

    private static function normalizeIdentifier(string $value): string
    {
        return Str::of($value)
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z0-9]+/', '')
            ->value();
    }
}

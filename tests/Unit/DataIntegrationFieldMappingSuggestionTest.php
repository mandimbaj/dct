<?php

namespace Tests\Unit;

use App\Models\DataIntegrationFieldMapping;
use PHPUnit\Framework\TestCase;

class DataIntegrationFieldMappingSuggestionTest extends TestCase
{
    public function test_it_suggests_semantic_reference_mappings_and_keeps_target_ids_internal(): void
    {
        $mappings = collect(DataIntegrationFieldMapping::suggestMappings([
            'indicator name',
            'country_code',
            'data source',
            'measure_type',
            'year',
            'value',
        ]))->keyBy('local_field');

        $this->assertSame('indicator name', $mappings['indicator_id']['external_field']);
        $this->assertSame('name', $mappings['indicator_id']['reference_match']);
        $this->assertSame('lookup', $mappings['indicator_id']['field_type']);
        $this->assertSame('country_code', $mappings['location_id']['external_field']);
        $this->assertSame('code', $mappings['location_id']['reference_match']);
        $this->assertSame('year', $mappings['period']['external_field']);
        $this->assertSame('value', $mappings['value_received']['external_field']);
    }
}

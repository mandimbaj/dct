<?php

namespace Tests\Unit;

use App\Support\CountryContext;
use ReflectionMethod;
use Tests\TestCase;

class CountryContextTest extends TestCase
{
    public function test_country_codes_are_normalized_for_flags_and_maps(): void
    {
        $method = new ReflectionMethod(CountryContext::class, 'iso2');

        $this->assertSame('SN', $method->invoke(null, 'SEN'));
        $this->assertSame('CV', $method->invoke(null, 'CPV'));
        $this->assertSame('TD', $method->invoke(null, 'TCD'));
        $this->assertSame('ML', $method->invoke(null, 'MLI'));
        $this->assertSame('SN', $method->invoke(null, 'sn'));
    }
}

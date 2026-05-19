<?php

namespace App\Filament\Resources\HealthIndicatorValues\Schemas;

use App\Models\HealthIndicatorValue;
use App\Support\WarehouseForm;
use Filament\Schemas\Schema;

class HealthIndicatorValueForm
{
    public static function configure(Schema $schema): Schema
    {
        return WarehouseForm::configure($schema, HealthIndicatorValue::class);
    }
}

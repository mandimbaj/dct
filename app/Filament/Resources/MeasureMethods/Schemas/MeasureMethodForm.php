<?php

namespace App\Filament\Resources\MeasureMethods\Schemas;

use App\Models\MeasureMethod;
use App\Support\TranslatedReferenceForm;
use Filament\Forms\Components\Hidden;
use Filament\Schemas\Schema;

class MeasureMethodForm
{
    public static function configure(Schema $schema): Schema
    {
        return TranslatedReferenceForm::configure(
            schema: $schema,
            modelClass: MeasureMethod::class,
            baseComponents: [
                Hidden::make('code'),
                Hidden::make('uuid'),
            ],
            includeIdentityComponents: false,
        );
    }
}

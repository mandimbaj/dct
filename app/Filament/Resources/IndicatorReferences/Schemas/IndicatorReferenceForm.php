<?php

namespace App\Filament\Resources\IndicatorReferences\Schemas;

use App\Models\IndicatorReference;
use App\Support\TranslatedReferenceForm;
use Filament\Forms\Components\Hidden;
use Filament\Schemas\Schema;

class IndicatorReferenceForm
{
    public static function configure(Schema $schema): Schema
    {
        return TranslatedReferenceForm::configure(
            schema: $schema,
            modelClass: IndicatorReference::class,
            baseComponents: [
                Hidden::make('code'),
                Hidden::make('uuid'),
            ],
            includeIdentityComponents: false,
        );
    }
}

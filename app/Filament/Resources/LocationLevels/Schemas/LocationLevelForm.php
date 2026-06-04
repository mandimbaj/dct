<?php

namespace App\Filament\Resources\LocationLevels\Schemas;

use App\Models\LocationLevel;
use App\Support\TranslatedReferenceForm;
use Filament\Forms\Components\Hidden;
use Filament\Schemas\Schema;

class LocationLevelForm
{
    public static function configure(Schema $schema): Schema
    {
        return TranslatedReferenceForm::configure(
            schema: $schema,
            modelClass: LocationLevel::class,
            baseComponents: [
                Hidden::make('code'),
                Hidden::make('uuid'),
            ],
            includeIdentityComponents: false,
        );
    }
}

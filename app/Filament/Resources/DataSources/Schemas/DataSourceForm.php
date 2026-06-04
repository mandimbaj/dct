<?php

namespace App\Filament\Resources\DataSources\Schemas;

use App\Models\DataSource;
use App\Support\TranslatedReferenceForm;
use Filament\Forms\Components\Hidden;
use Filament\Schemas\Schema;

class DataSourceForm
{
    public static function configure(Schema $schema): Schema
    {
        return TranslatedReferenceForm::configure(
            schema: $schema,
            modelClass: DataSource::class,
            baseComponents: [
                Hidden::make('code'),
                Hidden::make('uuid'),
            ],
            includeIdentityComponents: false,
        );
    }
}

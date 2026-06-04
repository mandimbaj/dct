<?php

namespace App\Filament\Resources\IndicatorCategories\Schemas;

use App\Models\IndicatorCategory;
use App\Support\TranslatedReferenceForm;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class IndicatorCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return TranslatedReferenceForm::configure(
            schema: $schema,
            modelClass: IndicatorCategory::class,
            baseComponents: [
                Hidden::make('code'),
                TextInput::make('category_id')
                    ->label(__('aho.fields.parent_category'))
                    ->required()
                    ->numeric(),
                Hidden::make('uuid'),
            ],
            includeIdentityComponents: false,
        );
    }
}

<?php

namespace App\Filament\Resources\IndicatorCategories\Schemas;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class IndicatorCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('code'),
                TextInput::make('category_id')
                    ->label(__('aho.fields.parent_category'))
                    ->required()
                    ->numeric(),
                Hidden::make('uuid'),
            ]);
    }
}

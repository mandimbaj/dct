<?php

namespace App\Filament\Resources\IndicatorCategories\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class IndicatorCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->label(__('aho.fields.code'))
                    ->required()
                    ->maxLength(230),
                TextInput::make('category_id')
                    ->label(__('aho.fields.parent_category'))
                    ->required()
                    ->numeric(),
                TextInput::make('uuid')
                    ->label(__('aho.fields.uuid'))
                    ->required()
                    ->maxLength(36)
                    ->columnSpanFull(),
            ]);
    }
}

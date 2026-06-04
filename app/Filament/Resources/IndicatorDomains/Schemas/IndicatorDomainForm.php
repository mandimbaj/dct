<?php

namespace App\Filament\Resources\IndicatorDomains\Schemas;

use App\Models\IndicatorDomain;
use App\Support\SelectOptions;
use App\Support\TranslatedReferenceForm;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class IndicatorDomainForm
{
    public static function configure(Schema $schema): Schema
    {
        return TranslatedReferenceForm::configure(
            schema: $schema,
            modelClass: IndicatorDomain::class,
            baseComponents: [
                Hidden::make('code'),
                TextInput::make('level')
                    ->label(__('aho.fields.level'))
                    ->required()
                    ->maxLength(50),
                Select::make('parent_id')
                    ->label(__('aho.fields.parent'))
                    ->relationship('parent', 'code', modifyQueryUsing: fn (Builder $query): Builder => SelectOptions::orderByDisplayName($query, 'code'))
                    ->getOptionLabelFromRecordUsing(fn ($record): string => $record->display_name)
                    ->options(fn (): array => SelectOptions::fromDisplayNameQuery(IndicatorDomain::query(), keyName: 'domain_id'))
                    ->getSearchResultsUsing(fn (?string $search): array => SelectOptions::fromDisplayNameQuery(IndicatorDomain::query(), $search, 'domain_id'))
                    ->searchable()
                    ->preload(),
                Hidden::make('uuid'),
            ],
            includeIdentityComponents: false,
        );
    }
}

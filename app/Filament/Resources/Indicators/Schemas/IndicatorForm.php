<?php

namespace App\Filament\Resources\Indicators\Schemas;

use App\Models\Indicator;
use App\Models\IndicatorReference;
use App\Support\SelectOptions;
use App\Support\TranslatedReferenceForm;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class IndicatorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('uuid'),

                Section::make(__('aho.form_sections.primary_attributes'))
                    ->schema([
                        Select::make('reference_id')
                            ->label(__('aho.fields.reference'))
                            ->relationship('reference', 'code', modifyQueryUsing: fn (Builder $query): Builder => SelectOptions::orderByDisplayName($query, 'code'))
                            ->getOptionLabelFromRecordUsing(fn ($record): string => $record->display_name)
                            ->options(fn (): array => SelectOptions::fromDisplayNameQuery(IndicatorReference::query(), keyName: 'reference_id'))
                            ->getSearchResultsUsing(fn (?string $search): array => SelectOptions::fromDisplayNameQuery(IndicatorReference::query(), $search, 'reference_id'))
                            ->searchable()
                            ->required(),
                        Hidden::make('afrocode'),
                        Hidden::make('gen_code'),
                    ])
                    ->columns(2),

                TranslatedReferenceForm::translationsSection(Indicator::class),
            ]);
    }
}

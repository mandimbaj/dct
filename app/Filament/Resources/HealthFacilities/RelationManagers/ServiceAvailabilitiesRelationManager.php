<?php

namespace App\Filament\Resources\HealthFacilities\RelationManagers;

use App\Filament\Resources\HealthFacilities\RelationManagers\Concerns\ConfiguresFacilityServiceOptions;
use App\Models\FacilityServiceAvailability;
use App\Support\FacilityServiceRecordUniqueness;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ServiceAvailabilitiesRelationManager extends RelationManager
{
    use ConfiguresFacilityServiceOptions;

    protected static string $relationship = 'serviceAvailabilities';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('aho.resources.service_availability.navigation');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('domain_id')
                    ->label(__('aho.fields.service_domain'))
                    ->options(fn (): array => self::domainOptions(1))
                    ->getSearchResultsUsing(fn (?string $search): array => self::domainOptions(1, null, $search))
                    ->live()
                    ->searchable()
                    ->required(),
                Select::make('intervention_id')
                    ->label(__('aho.fields.service_intervention'))
                    ->options(fn (Get $get): array => self::interventionOptions($get('domain_id')))
                    ->getSearchResultsUsing(fn (Get $get, ?string $search): array => self::interventionOptions($get('domain_id'), $search))
                    ->disabled(fn (Get $get): bool => blank($get('domain_id')))
                    ->live()
                    ->searchable()
                    ->required(),
                Select::make('service_id')
                    ->label(__('aho.fields.service_area'))
                    ->options(fn (Get $get): array => self::serviceAreaOptions($get('intervention_id')))
                    ->getSearchResultsUsing(fn (Get $get, ?string $search): array => self::serviceAreaOptions($get('intervention_id'), $search))
                    ->disabled(fn (Get $get): bool => blank($get('intervention_id')))
                    ->searchable()
                    ->required(),
                Toggle::make('provided')->label(__('aho.fields.provided')),
                Toggle::make('specialunit')->label(__('aho.fields.specialunit')),
                Toggle::make('staff')->label(__('aho.fields.staff')),
                Toggle::make('infrastructure')->label(__('aho.fields.infrastructure')),
                Toggle::make('supplies')->label(__('aho.fields.supplies')),
                DatePicker::make('date_assessed')
                    ->label(__('aho.fields.date_assessed'))
                    ->default(now())
                    ->rule(fn (Get $get, ?Model $record) => FacilityServiceRecordUniqueness::rule(
                        FacilityServiceAvailability::class,
                        [
                            'facility_id' => $this->getOwnerRecord()->facility_id,
                            'domain_id' => $get('domain_id'),
                            'intervention_id' => $get('intervention_id'),
                            'service_id' => $get('service_id'),
                        ],
                        $record,
                    ))
                    ->required(),
            ])
            ->columns(3);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('display_name')
            ->columns([
                TextColumn::make('domain.display_name')->label(__('aho.fields.service_domain'))->wrap()->sortable(),
                TextColumn::make('intervention.display_name')->label(__('aho.fields.service_intervention'))->wrap(),
                TextColumn::make('serviceArea.display_name')->label(__('aho.fields.service_area'))->wrap(),
                static::booleanColumn('provided', __('aho.fields.provided')),
                static::booleanColumn('staff', __('aho.fields.staff'))->toggleable(isToggledHiddenByDefault: true),
                static::booleanColumn('infrastructure', __('aho.fields.infrastructure'))->toggleable(isToggledHiddenByDefault: true),
                static::booleanColumn('supplies', __('aho.fields.supplies'))->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('date_assessed')->label(__('aho.fields.date_assessed'))->date()->sortable(),
                TextColumn::make('date_created')->label(__('aho.fields.creation'))->dateTime()->sortable()->toggleable(),
                TextColumn::make('date_lastupdated')->label(__('aho.fields.modification'))->dateTime()->sortable()->toggleable(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    private static function booleanColumn(string $name, string $label): TextColumn
    {
        return TextColumn::make($name)
            ->label($label)
            ->formatStateUsing(fn ($state): string => $state ? __('aho.fields.yes') : __('aho.fields.no'))
            ->badge();
    }
}

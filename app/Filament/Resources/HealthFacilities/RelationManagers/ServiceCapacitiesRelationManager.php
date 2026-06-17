<?php

namespace App\Filament\Resources\HealthFacilities\RelationManagers;

use App\Filament\Resources\HealthFacilities\RelationManagers\Concerns\ConfiguresFacilityServiceOptions;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ServiceCapacitiesRelationManager extends RelationManager
{
    use ConfiguresFacilityServiceOptions;

    protected static string $relationship = 'serviceCapacities';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('aho.resources.service_capacity.navigation');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('domain_id')
                    ->label(__('aho.fields.service_domain'))
                    ->options(fn (): array => self::domainOptions(2, 'Level 2'))
                    ->getSearchResultsUsing(fn (?string $search): array => self::domainOptions(2, 'Level 2', $search))
                    ->live()
                    ->searchable()
                    ->required(),
                Select::make('units_id')
                    ->label(__('aho.fields.provision_unit'))
                    ->options(fn (Get $get): array => self::unitOptions($get('domain_id')))
                    ->getSearchResultsUsing(fn (Get $get, ?string $search): array => self::unitOptions($get('domain_id'), $search))
                    ->disabled(fn (Get $get): bool => blank($get('domain_id')))
                    ->searchable()
                    ->required(),
                TextInput::make('available')
                    ->label(__('aho.fields.available'))
                    ->numeric()
                    ->minValue(0)
                    ->required(),
                TextInput::make('functional')
                    ->label(__('aho.fields.functional'))
                    ->numeric()
                    ->minValue(0)
                    ->required(),
                DatePicker::make('date_assessed')
                    ->label(__('aho.fields.date_assessed'))
                    ->default(now())
                    ->required(),
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('display_name')
            ->columns([
                TextColumn::make('domain.display_name')->label(__('aho.fields.service_domain'))->wrap()->sortable(),
                TextColumn::make('unit.display_name')->label(__('aho.fields.provision_unit'))->wrap(),
                TextColumn::make('available')->label(__('aho.fields.available'))->numeric()->sortable(),
                TextColumn::make('functional')->label(__('aho.fields.functional'))->numeric()->sortable(),
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
}

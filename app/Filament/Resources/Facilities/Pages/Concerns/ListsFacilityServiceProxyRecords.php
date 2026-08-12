<?php

namespace App\Filament\Resources\Facilities\Pages\Concerns;

use App\Filament\Resources\HealthFacilities\HealthFacilityResource;
use App\Models\HealthFacility;
use App\Support\CountryTableFilter;
use App\Support\FilamentSearch;
use App\Support\HeavyTable;
use App\Support\SelectOptions;
use App\Support\StatusColor;
use App\Support\UserCountryAccess;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

trait ListsFacilityServiceProxyRecords
{
    abstract protected function serviceRelationship(): string;

    abstract protected function serviceCountColumn(): string;

    abstract protected function serviceLatestAssessmentColumn(): string;

    abstract protected function serviceRelationIndex(): int;

    protected function getTableQuery(): Builder
    {
        $relationship = $this->serviceRelationship();

        return UserCountryAccess::scope(
            HealthFacility::query()
                ->with(['location.translations', 'type.translations', 'owner.translations'])
                ->withCount($relationship)
                ->withMax($relationship, 'date_assessed'),
            'location_id',
        );
    }

    public function table(Table $table): Table
    {
        return HeavyTable::configure($table)
            ->defaultSort('facility_id', 'desc')
            ->recordUrl(fn (HealthFacility $record): string => $this->facilityServicesUrl($record))
            ->searchUsing(function (Builder $query, string $search): void {
                FilamentSearch::apply(
                    query: $query,
                    search: $search,
                    columns: ['name', 'shortname', 'code', 'status', 'admin_location'],
                    relations: [
                        'location' => ['code', 'iso_alpha', 'iso_number'],
                        'location.translations' => ['name'],
                        'type' => ['code'],
                        'type.translations' => ['name'],
                        'owner' => ['code'],
                        'owner.translations' => ['name'],
                    ],
                    numericColumns: ['facility_id'],
                );
            })
            ->columns([
                TextColumn::make('facility_id')->label(__('aho.fields.id'))->sortable()->toggleable(),
                TextColumn::make('display_name')->label(__('aho.fields.facility'))->searchable(['name', 'shortname', 'code'])->wrap(),
                TextColumn::make('code')->label(__('aho.fields.code'))->searchable()->sortable()->toggleable(),
                TextColumn::make('type.display_name')->label(__('aho.fields.type'))->wrap()->toggleable(),
                TextColumn::make('owner.display_name')->label(__('aho.fields.owner'))->wrap()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('location.display_name')->label(__('aho.fields.location'))->wrap()->toggleable(),
                TextColumn::make($this->serviceCountColumn())->label(__('aho.fields.service_records'))->numeric()->sortable(),
                TextColumn::make($this->serviceLatestAssessmentColumn())->label(__('aho.fields.latest_assessment'))->date()->sortable()->toggleable(),
                TextColumn::make('status')
                    ->label(__('aho.fields.status'))
                    ->badge()
                    ->color(fn (?string $state): string => StatusColor::for($state))
                    ->formatStateUsing(fn (?string $state): string => [
                        'active' => __('aho.status.active'),
                        'closed' => __('aho.status.closed'),
                    ][$state] ?? (string) $state)
                    ->toggleable(),
            ])
            ->filters([
                CountryTableFilter::make(),
                SelectFilter::make('type_id')
                    ->label(__('aho.fields.type'))
                    ->relationship('type', 'code', modifyQueryUsing: fn (Builder $query): Builder => SelectOptions::orderByDisplayName($query, 'code'))
                    ->getOptionLabelFromRecordUsing(fn ($record): string => $record->display_name)
                    ->searchable(),
            ])
            ->recordActions([
                Action::make('manage_services')
                    ->label(__('aho.actions.manage_services'))
                    ->url(fn (HealthFacility $record): string => $this->facilityServicesUrl($record)),
            ])
            ->toolbarActions([]);
    }

    protected function countryRouteParameter(): string
    {
        return (string) (request()->route('country') ?: request()->segment(2) ?: 'af');
    }

    protected function facilityServicesUrl(HealthFacility $record): string
    {
        return HealthFacilityResource::getUrl('edit', [
            'country' => $this->countryRouteParameter(),
            'record' => $record,
            'relation' => $this->serviceRelationIndex(),
        ]);
    }
}

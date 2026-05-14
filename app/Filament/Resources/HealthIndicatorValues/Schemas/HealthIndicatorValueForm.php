<?php

namespace App\Filament\Resources\HealthIndicatorValues\Schemas;

use App\Filament\Resources\HealthIndicatorValues\HealthIndicatorValueResource;
use App\Support\ApprovalWorkflow;
use App\Support\UserCountryAccess;
use App\Support\UserPermissions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class HealthIndicatorValueForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('indicator_id')
                    ->label(__('aho.fields.indicator'))
                    ->relationship('indicator', 'afrocode')
                    ->getOptionLabelFromRecordUsing(fn ($record): string => trim(($record->afrocode ? "{$record->afrocode} - " : '').$record->display_name))
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('location_id')
                    ->label(__('aho.fields.location'))
                    ->relationship('location', 'code', modifyQueryUsing: fn ($query) => UserCountryAccess::scope($query, 'location_id'))
                    ->getOptionLabelFromRecordUsing(fn ($record): string => trim(($record->code ? "{$record->code} - " : '').$record->display_name))
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('start_period')
                    ->label(__('aho.fields.start'))
                    ->numeric()
                    ->required(),
                TextInput::make('end_period')
                    ->label(__('aho.fields.end'))
                    ->numeric()
                    ->required(),
                TextInput::make('period')
                    ->label(__('aho.fields.period'))
                    ->maxLength(50)
                    ->required(),
                Select::make('categoryoption_id')
                    ->label(__('aho.fields.disaggregation'))
                    ->relationship('categoryOption', 'code')
                    ->getOptionLabelFromRecordUsing(fn ($record): string => trim(($record->code ? "{$record->code} - " : '').$record->display_name))
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('datasource_id')
                    ->label(__('aho.fields.source'))
                    ->relationship('dataSource', 'code')
                    ->getOptionLabelFromRecordUsing(fn ($record): string => trim(($record->code ? "{$record->code} - " : '').$record->display_name))
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('measuremethod_id')
                    ->label(__('aho.fields.method'))
                    ->relationship('measureMethod', 'code')
                    ->getOptionLabelFromRecordUsing(fn ($record): string => trim(($record->code ? "{$record->code} - " : '').$record->display_name))
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('value_received')
                    ->label(__('aho.fields.value_received'))
                    ->numeric()
                    ->step('0.000001')
                    ->required(),
                TextInput::make('numerator_value')
                    ->label(__('aho.fields.numerator'))
                    ->numeric()
                    ->step('0.000001'),
                TextInput::make('denominator_value')
                    ->label(__('aho.fields.denominator'))
                    ->numeric()
                    ->step('0.000001'),
                TextInput::make('min_value')
                    ->label(__('aho.fields.min'))
                    ->numeric()
                    ->step('0.000001'),
                TextInput::make('max_value')
                    ->label(__('aho.fields.max'))
                    ->numeric()
                    ->step('0.000001'),
                TextInput::make('target_value')
                    ->label(__('aho.fields.target'))
                    ->numeric()
                    ->step('0.000001'),
                TextInput::make('string_value')
                    ->label(__('aho.fields.text_value'))
                    ->maxLength(500)
                    ->columnSpanFull(),
                Select::make('comment')
                    ->label(__('aho.fields.approval_status'))
                    ->options(fn (): array => ApprovalWorkflow::options())
                    ->default(ApprovalWorkflow::STATUS_PENDING)
                    ->required(fn (): bool => (bool) (
                        auth()->user()
                        && UserPermissions::allowsResource(auth()->user(), HealthIndicatorValueResource::class, UserPermissions::ACTION_APPROVE)
                    ))
                    ->disabled(fn (): bool => ! (
                        auth()->user()
                        && UserPermissions::allowsResource(auth()->user(), HealthIndicatorValueResource::class, UserPermissions::ACTION_APPROVE)
                    ))
                    ->dehydrated(fn (): bool => (bool) (
                        auth()->user()
                        && UserPermissions::allowsResource(auth()->user(), HealthIndicatorValueResource::class, UserPermissions::ACTION_APPROVE)
                    )),
                Toggle::make('priority')
                    ->label(__('aho.fields.priority')),
                TextInput::make('uuid')
                    ->label(__('aho.fields.uuid'))
                    ->maxLength(36)
                    ->columnSpanFull(),
            ]);
    }
}

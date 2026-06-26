<?php

namespace App\Filament\Resources\DataIntegrationConnections\Tables;

use App\Filament\Resources\DataIntegrationConnections\DataIntegrationConnectionResource;
use App\Models\Country;
use App\Models\DataIntegrationConnection;
use App\Support\SelectOptions;
use App\Support\StatusColor;
use App\Support\UserCountryAccess;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DataIntegrationConnectionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label(__('aho.data_integration.fields.name'))
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('location_id')
                    ->label(__('aho.data_integration.fields.country'))
                    ->state(fn (DataIntegrationConnection $record): string => $record->location?->display_name
                        ?? __('aho.data_integration.regional_or_unassigned'))
                    ->searchable(query: fn ($query, string $search) => $query->whereIn(
                        'location_id',
                        Country::query()
                            ->whereHas('translations', fn ($translations) => $translations->where('name', 'like', "%{$search}%"))
                            ->pluck('location_id'),
                    )),
                TextColumn::make('provider')
                    ->label(__('aho.data_integration.fields.provider'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => DataIntegrationConnection::providerOptions()[$state] ?? (string) $state)
                    ->sortable(),
                TextColumn::make('integration_method')
                    ->label(__('aho.data_integration.fields.integration_method'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => DataIntegrationConnection::methodOptions()[$state] ?? (string) $state)
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('aho.data_integration.fields.status'))
                    ->badge()
                    ->color(fn (?string $state): string => StatusColor::for($state))
                    ->formatStateUsing(fn (?string $state): string => DataIntegrationConnection::statusOptions()[$state] ?? (string) $state)
                    ->sortable(),
                TextColumn::make('server_name')
                    ->label(__('aho.data_integration.fields.server_name'))
                    ->placeholder(__('aho.data_integration.not_applicable'))
                    ->limit(30)
                    ->toggleable(),
                TextColumn::make('ssl_mode')
                    ->label(__('aho.data_integration.fields.ssl_mode'))
                    ->state(fn (DataIntegrationConnection $record): ?string => $record->integration_method === DataIntegrationConnection::METHOD_DIRECT
                        ? $record->ssl_mode
                        : null)
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => DataIntegrationConnection::sslModeOptions()[$state] ?? (string) $state)
                    ->placeholder(__('aho.data_integration.not_applicable'))
                    ->toggleable(),
                TextColumn::make('api_url')
                    ->label(__('aho.data_integration.fields.api_url'))
                    ->placeholder(__('aho.data_integration.not_applicable'))
                    ->limit(40)
                    ->toggleable(),
                TextColumn::make('sync_frequency')
                    ->label(__('aho.data_integration.fields.sync_frequency'))
                    ->formatStateUsing(fn (?string $state): string => DataIntegrationConnection::syncFrequencyOptions()[$state] ?? (string) $state)
                    ->toggleable(),
                TextColumn::make('last_test_status')
                    ->label(__('aho.data_integration.fields.last_test_status'))
                    ->badge()
                    ->color(fn (?string $state): string => $state === 'ready' ? 'success' : ($state === 'missing' ? 'warning' : 'gray'))
                    ->placeholder(__('aho.data_integration.not_tested'))
                    ->toggleable(),
                TextColumn::make('field_mappings_count')
                    ->label(__('aho.data_integration.fields.field_mappings'))
                    ->counts('fieldMappings')
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'success' : 'warning')
                    ->formatStateUsing(fn (int $state): string => trans_choice('aho.data_integration.mapping_count', $state, ['count' => $state]))
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('last_synced_at')
                    ->label(__('aho.data_integration.fields.last_synced_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label(__('aho.fields.creation'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('updated_at')
                    ->label(__('aho.fields.modification'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('provider')
                    ->label(__('aho.data_integration.fields.provider'))
                    ->options(fn (): array => DataIntegrationConnection::providerOptions()),
                SelectFilter::make('integration_method')
                    ->label(__('aho.data_integration.fields.integration_method'))
                    ->options(fn (): array => DataIntegrationConnection::methodOptions()),
                SelectFilter::make('status')
                    ->label(__('aho.data_integration.fields.status'))
                    ->options(fn (): array => DataIntegrationConnection::statusOptions()),
                SelectFilter::make('location_id')
                    ->label(__('aho.data_integration.fields.country'))
                    ->options(fn (): array => SelectOptions::fromDisplayNameQuery(
                        UserCountryAccess::scopeLocations(Country::query()->where('locationlevel_id', 2)),
                        keyName: 'location_id',
                    )),
            ])
            ->recordActions([
                Action::make('configure_mapping')
                    ->label(__('aho.data_integration.actions.configure_mapping'))
                    ->icon('heroicon-o-arrows-right-left')
                    ->color(fn (DataIntegrationConnection $record): string => $record->hasConfiguredFieldMappings() ? 'gray' : 'warning')
                    ->url(fn (DataIntegrationConnection $record): string => DataIntegrationConnectionResource::getUrl('mapping', ['record' => $record])),
                Action::make('validate_configuration')
                    ->label(__('aho.actions.validate_configuration'))
                    ->icon('heroicon-o-check-circle')
                    ->action(function (DataIntegrationConnection $record): void {
                        $result = $record->validateConfiguration();

                        $record->forceFill([
                            'last_tested_at' => now(),
                            'last_test_status' => $result['ok'] ? 'ready' : 'missing',
                            'last_test_message' => $result['message'],
                        ])->save();

                        $notification = Notification::make()
                            ->title(__('aho.data_integration.validation.checked'))
                            ->body($result['message']);

                        $result['ok'] ? $notification->success() : $notification->warning();
                        $notification->send();
                    }),
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

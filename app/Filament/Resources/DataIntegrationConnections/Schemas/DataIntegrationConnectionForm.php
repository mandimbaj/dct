<?php

namespace App\Filament\Resources\DataIntegrationConnections\Schemas;

use App\Models\DataIntegrationConnection;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class DataIntegrationConnectionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('aho.data_integration.sections.identity'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('aho.data_integration.fields.name'))
                            ->required()
                            ->maxLength(255),
                        Select::make('provider')
                            ->label(__('aho.data_integration.fields.provider'))
                            ->options(fn (): array => DataIntegrationConnection::providerOptions())
                            ->default(DataIntegrationConnection::PROVIDER_DHIS2)
                            ->searchable()
                            ->required(),
                        Select::make('integration_method')
                            ->label(__('aho.data_integration.fields.integration_method'))
                            ->options(fn (): array => DataIntegrationConnection::methodOptions())
                            ->default(DataIntegrationConnection::METHOD_API)
                            ->live()
                            ->required(),
                        Select::make('status')
                            ->label(__('aho.data_integration.fields.status'))
                            ->options(fn (): array => DataIntegrationConnection::statusOptions())
                            ->default(DataIntegrationConnection::STATUS_DRAFT)
                            ->required(),
                        Select::make('sync_frequency')
                            ->label(__('aho.data_integration.fields.sync_frequency'))
                            ->options(fn (): array => DataIntegrationConnection::syncFrequencyOptions())
                            ->default('manual')
                            ->required(),
                    ])
                    ->columns(2),

                Section::make(__('aho.data_integration.sections.direct'))
                    ->schema([
                        TextInput::make('server_name')
                            ->label(__('aho.data_integration.fields.server_name'))
                            ->maxLength(255)
                            ->required(fn (Get $get): bool => $get('integration_method') === DataIntegrationConnection::METHOD_DIRECT),
                        TextInput::make('port')
                            ->label(__('aho.data_integration.fields.port'))
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(65535),
                        Select::make('database_driver')
                            ->label(__('aho.data_integration.fields.database_driver'))
                            ->options(fn (): array => DataIntegrationConnection::databaseDriverOptions())
                            ->searchable()
                            ->required(fn (Get $get): bool => $get('integration_method') === DataIntegrationConnection::METHOD_DIRECT),
                        TextInput::make('database_name')
                            ->label(__('aho.data_integration.fields.database_name'))
                            ->maxLength(255)
                            ->required(fn (Get $get): bool => $get('integration_method') === DataIntegrationConnection::METHOD_DIRECT),
                    ])
                    ->columns(2)
                    ->visible(fn (Get $get): bool => $get('integration_method') === DataIntegrationConnection::METHOD_DIRECT),

                Section::make(__('aho.data_integration.sections.api'))
                    ->schema([
                        TextInput::make('api_url')
                            ->label(__('aho.data_integration.fields.api_url'))
                            ->url()
                            ->maxLength(255)
                            ->required(fn (Get $get): bool => $get('integration_method') === DataIntegrationConnection::METHOD_API)
                            ->columnSpanFull(),
                        Select::make('auth_type')
                            ->label(__('aho.data_integration.fields.auth_type'))
                            ->options(fn (): array => DataIntegrationConnection::authTypeOptions())
                            ->default('none')
                            ->live()
                            ->required(),
                        TextInput::make('api_token')
                            ->label(__('aho.data_integration.fields.api_token'))
                            ->password()
                            ->revealable()
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->required(fn (Get $get, string $operation): bool => $operation === 'create' && $get('auth_type') === 'bearer')
                            ->visible(fn (Get $get): bool => $get('auth_type') === 'bearer')
                            ->columnSpanFull(),
                        TextInput::make('api_key_name')
                            ->label(__('aho.data_integration.fields.api_key_name'))
                            ->maxLength(255)
                            ->required(fn (Get $get): bool => $get('auth_type') === 'api_key')
                            ->visible(fn (Get $get): bool => $get('auth_type') === 'api_key'),
                        TextInput::make('api_key_value')
                            ->label(__('aho.data_integration.fields.api_key_value'))
                            ->password()
                            ->revealable()
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->required(fn (Get $get, string $operation): bool => $operation === 'create' && $get('auth_type') === 'api_key')
                            ->visible(fn (Get $get): bool => $get('auth_type') === 'api_key'),
                        TextInput::make('client_id')
                            ->label(__('aho.data_integration.fields.client_id'))
                            ->maxLength(255)
                            ->required(fn (Get $get): bool => $get('auth_type') === 'oauth2')
                            ->visible(fn (Get $get): bool => $get('auth_type') === 'oauth2'),
                        TextInput::make('client_secret')
                            ->label(__('aho.data_integration.fields.client_secret'))
                            ->password()
                            ->revealable()
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->required(fn (Get $get, string $operation): bool => $operation === 'create' && $get('auth_type') === 'oauth2')
                            ->visible(fn (Get $get): bool => $get('auth_type') === 'oauth2'),
                    ])
                    ->columns(2)
                    ->visible(fn (Get $get): bool => $get('integration_method') === DataIntegrationConnection::METHOD_API),

                Section::make(__('aho.data_integration.sections.credentials'))
                    ->schema([
                        TextInput::make('username')
                            ->label(__('aho.data_integration.fields.username'))
                            ->maxLength(255)
                            ->required(fn (Get $get): bool => $get('integration_method') === DataIntegrationConnection::METHOD_DIRECT || $get('auth_type') === 'basic'),
                        TextInput::make('password')
                            ->label(__('aho.data_integration.fields.password'))
                            ->password()
                            ->revealable()
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->required(fn (Get $get, string $operation): bool => $operation === 'create' && ($get('integration_method') === DataIntegrationConnection::METHOD_DIRECT || $get('auth_type') === 'basic')),
                    ])
                    ->columns(2)
                    ->visible(fn (Get $get): bool => $get('integration_method') === DataIntegrationConnection::METHOD_DIRECT || $get('auth_type') === 'basic'),

                Section::make(__('aho.data_integration.sections.mapping'))
                    ->schema([
                        KeyValue::make('data_scope')
                            ->label(__('aho.data_integration.fields.data_scope'))
                            ->keyLabel(__('aho.data_integration.fields.scope_key'))
                            ->valueLabel(__('aho.data_integration.fields.scope_value')),
                        KeyValue::make('field_mapping')
                            ->label(__('aho.data_integration.fields.field_mapping'))
                            ->keyLabel(__('aho.data_integration.fields.local_field'))
                            ->valueLabel(__('aho.data_integration.fields.external_field')),
                        Textarea::make('notes')
                            ->label(__('aho.data_integration.fields.notes'))
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}

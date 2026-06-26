<?php

namespace App\Filament\Resources\DataIntegrationConnections\Schemas;

use App\Models\Country;
use App\Models\DataIntegrationConnection;
use App\Support\DataIntegration\ExternalDatabaseMetadata;
use App\Support\SelectOptions;
use App\Support\UserCountryAccess;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

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
                        Select::make('location_id')
                            ->label(__('aho.data_integration.fields.country'))
                            ->options(fn (): array => SelectOptions::fromDisplayNameQuery(
                                UserCountryAccess::scopeLocations(Country::query()->where('locationlevel_id', 2)),
                                keyName: 'location_id',
                            ))
                            ->getSearchResultsUsing(fn (?string $search): array => SelectOptions::fromDisplayNameQuery(
                                UserCountryAccess::scopeLocations(Country::query()->where('locationlevel_id', 2)),
                                $search,
                                'location_id',
                            ))
                            ->default(fn (): ?int => UserCountryAccess::canViewAllCountries() ? null : UserCountryAccess::locationId())
                            ->disabled(fn (): bool => ! UserCountryAccess::canViewAllCountries())
                            ->dehydrated()
                            ->searchable()
                            ->preload()
                            ->required(fn (): bool => ! UserCountryAccess::canViewAllCountries()),
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
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Set $set): mixed => self::resetDirectSelection($set))
                            ->maxLength(255)
                            ->required(fn (Get $get): bool => $get('integration_method') === DataIntegrationConnection::METHOD_DIRECT),
                        TextInput::make('port')
                            ->label(__('aho.data_integration.fields.port'))
                            ->numeric()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Set $set): mixed => self::resetDirectSelection($set))
                            ->minValue(1)
                            ->maxValue(65535),
                        Select::make('database_driver')
                            ->label(__('aho.data_integration.fields.database_driver'))
                            ->options(fn (): array => DataIntegrationConnection::databaseDriverOptions())
                            ->live()
                            ->afterStateUpdated(fn (Set $set): mixed => self::resetDirectSelection($set))
                            ->searchable()
                            ->required(fn (Get $get): bool => $get('integration_method') === DataIntegrationConnection::METHOD_DIRECT),
                        Select::make('database_name')
                            ->label(__('aho.data_integration.fields.database_name'))
                            ->helperText(__('aho.data_integration.help.database_name'))
                            ->options(fn (Get $get, ?Model $record): array => self::databaseOptions($get, $record))
                            ->createOptionForm([
                                TextInput::make('database_name')
                                    ->label(__('aho.data_integration.fields.database_name'))
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->createOptionUsing(fn (array $data): string => $data['database_name'])
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(fn (Set $set): mixed => $set('source_table', null))
                            ->required(fn (Get $get): bool => $get('integration_method') === DataIntegrationConnection::METHOD_DIRECT),
                        Select::make('source_table')
                            ->label(__('aho.data_integration.fields.source_table'))
                            ->helperText(__('aho.data_integration.help.source_table'))
                            ->options(fn (Get $get, ?Model $record): array => self::relationOptions($get, $record))
                            ->createOptionForm([
                                TextInput::make('source_table')
                                    ->label(__('aho.data_integration.fields.source_table'))
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->createOptionUsing(fn (array $data): string => $data['source_table'])
                            ->searchable()
                            ->required(fn (Get $get): bool => $get('integration_method') === DataIntegrationConnection::METHOD_DIRECT),
                        TextInput::make('connection_timeout')
                            ->label(__('aho.data_integration.fields.connection_timeout'))
                            ->helperText(__('aho.data_integration.help.connection_timeout'))
                            ->numeric()
                            ->default(15)
                            ->minValue(1)
                            ->maxValue(120)
                            ->required(),
                    ])
                    ->columns(2)
                    ->visible(fn (Get $get): bool => $get('integration_method') === DataIntegrationConnection::METHOD_DIRECT),

                Section::make(__('aho.data_integration.sections.ssl'))
                    ->description(__('aho.data_integration.help.ssl_mode'))
                    ->schema([
                        Select::make('ssl_mode')
                            ->label(__('aho.data_integration.fields.ssl_mode'))
                            ->options(fn (): array => DataIntegrationConnection::sslModeOptions())
                            ->default(DataIntegrationConnection::SSL_MODE_DISABLED)
                            ->live()
                            ->required(),
                        TextInput::make('ssl_ca_path')
                            ->label(__('aho.data_integration.fields.ssl_ca_path'))
                            ->helperText(__('aho.data_integration.help.ssl_ca_path'))
                            ->maxLength(1000)
                            ->visible(fn (Get $get): bool => $get('ssl_mode') !== DataIntegrationConnection::SSL_MODE_DISABLED
                                && in_array($get('database_driver'), ['mysql', 'pgsql'], true)),
                        TextInput::make('ssl_certificate_path')
                            ->label(__('aho.data_integration.fields.ssl_certificate_path'))
                            ->helperText(__('aho.data_integration.help.ssl_client_certificate'))
                            ->maxLength(1000)
                            ->visible(fn (Get $get): bool => $get('ssl_mode') !== DataIntegrationConnection::SSL_MODE_DISABLED
                                && in_array($get('database_driver'), ['mysql', 'pgsql'], true)),
                        TextInput::make('ssl_key_path')
                            ->label(__('aho.data_integration.fields.ssl_key_path'))
                            ->helperText(__('aho.data_integration.help.ssl_client_certificate'))
                            ->maxLength(1000)
                            ->visible(fn (Get $get): bool => $get('ssl_mode') !== DataIntegrationConnection::SSL_MODE_DISABLED
                                && in_array($get('database_driver'), ['mysql', 'pgsql'], true)),
                        TextInput::make('ssl_cipher')
                            ->label(__('aho.data_integration.fields.ssl_cipher'))
                            ->helperText(__('aho.data_integration.help.ssl_cipher'))
                            ->maxLength(255)
                            ->visible(fn (Get $get): bool => $get('ssl_mode') !== DataIntegrationConnection::SSL_MODE_DISABLED
                                && $get('database_driver') === 'mysql'),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->visible(fn (Get $get): bool => $get('integration_method') === DataIntegrationConnection::METHOD_DIRECT
                        && in_array($get('database_driver'), ['mysql', 'pgsql', 'sqlsrv'], true)),

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
                            ->live(onBlur: true)
                            ->maxLength(255)
                            ->required(fn (Get $get): bool => $get('integration_method') === DataIntegrationConnection::METHOD_DIRECT || $get('auth_type') === 'basic'),
                        TextInput::make('password')
                            ->label(__('aho.data_integration.fields.password'))
                            ->password()
                            ->revealable()
                            ->live(onBlur: true)
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->required(fn (Get $get, string $operation): bool => $operation === 'create' && (
                                ($get('integration_method') === DataIntegrationConnection::METHOD_DIRECT && DataIntegrationConnection::requiresDirectConnectionPassword($get('server_name')))
                                || ($get('integration_method') === DataIntegrationConnection::METHOD_API && $get('auth_type') === 'basic')
                            )),
                    ])
                    ->columns(2)
                    ->visible(fn (Get $get): bool => $get('integration_method') === DataIntegrationConnection::METHOD_DIRECT || $get('auth_type') === 'basic'),

                Section::make(__('aho.data_integration.sections.mapping'))
                    ->schema([
                        Textarea::make('notes')
                            ->label(__('aho.data_integration.fields.notes'))
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    private static function resetDirectSelection(Set $set): null
    {
        ExternalDatabaseMetadata::clearCache();
        $set('database_name', null);
        $set('source_table', null);

        return null;
    }

    /** @return array<string, string> */
    private static function databaseOptions(Get $get, ?Model $record): array
    {
        $connection = self::formConnection($get, $record);
        $fallback = self::selectedOption($get('database_name'));

        if (! self::canDiscoverDatabases($connection)) {
            return $fallback;
        }

        try {
            return self::withSelectedOption(
                ExternalDatabaseMetadata::databases($connection),
                $get('database_name'),
            );
        } catch (\Throwable) {
            return $fallback;
        }
    }

    /** @return array<string, string> */
    private static function relationOptions(Get $get, ?Model $record): array
    {
        $connection = self::formConnection($get, $record);
        $fallback = self::selectedOption($get('source_table'));

        if (! self::canDiscoverDatabases($connection) || blank($connection->database_name)) {
            return $fallback;
        }

        try {
            return self::withSelectedOption(
                ExternalDatabaseMetadata::relations($connection),
                $get('source_table'),
            );
        } catch (\Throwable) {
            return $fallback;
        }
    }

    private static function formConnection(Get $get, ?Model $record): DataIntegrationConnection
    {
        $connection = $record instanceof DataIntegrationConnection
            ? clone $record
            : new DataIntegrationConnection;

        foreach ([
            'integration_method',
            'server_name',
            'port',
            'database_driver',
            'database_name',
            'username',
            'ssl_mode',
            'ssl_ca_path',
            'ssl_certificate_path',
            'ssl_key_path',
            'ssl_cipher',
            'connection_timeout',
        ] as $field) {
            $connection->{$field} = $get($field);
        }

        if (filled($get('password'))) {
            $connection->password = $get('password');
        }

        return $connection;
    }

    private static function canDiscoverDatabases(DataIntegrationConnection $connection): bool
    {
        if (blank($connection->database_driver)) {
            return false;
        }

        if ($connection->database_driver === 'sqlite') {
            return true;
        }

        if (blank($connection->server_name) || blank($connection->username)) {
            return false;
        }

        return ! DataIntegrationConnection::requiresDirectConnectionPassword($connection->server_name)
            || filled($connection->password);
    }

    /** @return array<string, string> */
    private static function selectedOption(mixed $selected): array
    {
        $selected = trim((string) $selected);

        return filled($selected) ? [$selected => $selected] : [];
    }

    /**
     * @param  array<string, string>  $options
     * @return array<string, string>
     */
    private static function withSelectedOption(array $options, mixed $selected): array
    {
        return $options + self::selectedOption($selected);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DataIntegrationConnection extends Model
{
    public const PROVIDER_DHIS2 = 'dhis2';

    public const PROVIDER_DATABANK = 'databank';

    public const PROVIDER_WHO_DATAHUB = 'who_datahub';

    public const PROVIDER_AHO_WAREHOUSE = 'aho_warehouse';

    public const PROVIDER_CUSTOM = 'custom';

    public const METHOD_DIRECT = 'direct';

    public const METHOD_API = 'api';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_PAUSED = 'paused';

    public const STATUS_ERROR = 'error';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'password' => 'encrypted',
            'api_token' => 'encrypted',
            'api_key_value' => 'encrypted',
            'client_secret' => 'encrypted',
            'data_scope' => 'array',
            'field_mapping' => 'array',
            'last_synced_at' => 'datetime',
            'last_tested_at' => 'datetime',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function providerOptions(): array
    {
        return [
            self::PROVIDER_DHIS2 => __('aho.data_integration.providers.dhis2'),
            self::PROVIDER_DATABANK => __('aho.data_integration.providers.databank'),
            self::PROVIDER_WHO_DATAHUB => __('aho.data_integration.providers.who_datahub'),
            self::PROVIDER_AHO_WAREHOUSE => __('aho.data_integration.providers.aho_warehouse'),
            self::PROVIDER_CUSTOM => __('aho.data_integration.providers.custom'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function methodOptions(): array
    {
        return [
            self::METHOD_DIRECT => __('aho.data_integration.methods.direct'),
            self::METHOD_API => __('aho.data_integration.methods.api'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function statusOptions(): array
    {
        return [
            self::STATUS_DRAFT => __('aho.data_integration.statuses.draft'),
            self::STATUS_ACTIVE => __('aho.data_integration.statuses.active'),
            self::STATUS_PAUSED => __('aho.data_integration.statuses.paused'),
            self::STATUS_ERROR => __('aho.data_integration.statuses.error'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function authTypeOptions(): array
    {
        return [
            'none' => __('aho.data_integration.auth.none'),
            'bearer' => __('aho.data_integration.auth.bearer'),
            'api_key' => __('aho.data_integration.auth.api_key'),
            'basic' => __('aho.data_integration.auth.basic'),
            'oauth2' => __('aho.data_integration.auth.oauth2'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function databaseDriverOptions(): array
    {
        return [
            'mysql' => 'MySQL / MariaDB',
            'pgsql' => 'PostgreSQL',
            'sqlsrv' => 'SQL Server',
            'oracle' => 'Oracle',
            'sqlite' => 'SQLite',
            'odbc' => 'ODBC',
            'other' => __('aho.data_integration.other'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function syncFrequencyOptions(): array
    {
        return [
            'manual' => __('aho.data_integration.frequencies.manual'),
            'hourly' => __('aho.data_integration.frequencies.hourly'),
            'daily' => __('aho.data_integration.frequencies.daily'),
            'weekly' => __('aho.data_integration.frequencies.weekly'),
            'monthly' => __('aho.data_integration.frequencies.monthly'),
        ];
    }

    /**
     * @return array{ok: bool, message: string}
     */
    public function validateConfiguration(): array
    {
        $missing = [];

        if ($this->integration_method === self::METHOD_DIRECT) {
            $missing = [
                ...$missing,
                ...$this->missingFields([
                    'server_name' => __('aho.data_integration.fields.server_name'),
                    'database_driver' => __('aho.data_integration.fields.database_driver'),
                    'database_name' => __('aho.data_integration.fields.database_name'),
                    'username' => __('aho.data_integration.fields.username'),
                    'password' => __('aho.data_integration.fields.password'),
                ]),
            ];
        }

        if ($this->integration_method === self::METHOD_API) {
            $missing = [
                ...$missing,
                ...$this->missingFields([
                    'api_url' => __('aho.data_integration.fields.api_url'),
                ]),
            ];

            $missing = [
                ...$missing,
                ...match ($this->auth_type) {
                    'bearer' => $this->missingFields(['api_token' => __('aho.data_integration.fields.api_token')]),
                    'api_key' => $this->missingFields([
                        'api_key_name' => __('aho.data_integration.fields.api_key_name'),
                        'api_key_value' => __('aho.data_integration.fields.api_key_value'),
                    ]),
                    'basic' => $this->missingFields([
                        'username' => __('aho.data_integration.fields.username'),
                        'password' => __('aho.data_integration.fields.password'),
                    ]),
                    'oauth2' => $this->missingFields([
                        'client_id' => __('aho.data_integration.fields.client_id'),
                        'client_secret' => __('aho.data_integration.fields.client_secret'),
                    ]),
                    default => [],
                },
            ];
        }

        if ($missing !== []) {
            return [
                'ok' => false,
                'message' => __('aho.data_integration.validation.missing', ['fields' => implode(', ', $missing)]),
            ];
        }

        return [
            'ok' => true,
            'message' => __('aho.data_integration.validation.ready'),
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all field mappings for this connection
     */
    public function fieldMappings(): HasMany
    {
        return $this->hasMany(DataIntegrationFieldMapping::class);
    }

    /**
     * Check if field mappings have been configured
     */
    public function hasFieldMappings(): bool
    {
        return $this->fieldMappings()->exists();
    }

    protected static function booted(): void
    {
        static::creating(function (DataIntegrationConnection $connection): void {
            $connection->user_id ??= auth()->id();
        });
    }

    /**
     * @param  array<string, string>  $fields
     * @return array<int, string>
     */
    private function missingFields(array $fields): array
    {
        $missing = [];

        foreach ($fields as $field => $label) {
            if (blank($this->{$field})) {
                $missing[] = $label;
            }
        }

        return $missing;
    }
}

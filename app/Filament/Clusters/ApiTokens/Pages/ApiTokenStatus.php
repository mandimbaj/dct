<?php

namespace App\Filament\Clusters\ApiTokens\Pages;

use App\Filament\Clusters\ApiTokens;
use App\Models\ApiToken;
use App\Models\Country;
use App\Models\DataSource;
use App\Models\HealthIndicatorValue;
use App\Models\Indicator;
use App\Models\IndicatorCategory;
use App\Models\MeasureMethod;
use App\Support\ApprovalWorkflow;
use App\Support\UserCountryAccess;
use App\Support\UserPermissions;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema as DatabaseSchema;
use Illuminate\Support\Str;
use Throwable;

class ApiTokenStatus extends Page
{
    protected static ?string $cluster = ApiTokens::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'status';

    protected string $view = 'filament.clusters.api-tokens.pages.api-token-status';

    public string $tokenName = 'Data Capture API';

    public ?string $expiresAt = null;

    public ?string $plainToken = null;

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.api_tokens.navigation');
    }

    public function getTitle(): string
    {
        return __('aho.resources.api_tokens.navigation');
    }

    public static function canAccess(): bool
    {
        return (bool) auth()->user() && UserPermissions::allowsPage(auth()->user(), static::class);
    }

    public function createToken(): void
    {
        $data = $this->validate([
            'tokenName' => ['required', 'string', 'max:120'],
            'expiresAt' => ['nullable', 'date', 'after_or_equal:today'],
        ]);

        $plainToken = 'dct_'.Str::random(48);

        ApiToken::create([
            'user_id' => auth()->id(),
            'name' => $data['tokenName'],
            'token_prefix' => Str::substr($plainToken, 0, 12),
            'token_hash' => hash('sha256', $plainToken),
            'abilities' => ['health-data:read', 'health-data:write'],
            'expires_at' => filled($data['expiresAt']) ? $data['expiresAt'] : null,
        ]);

        $this->plainToken = $plainToken;
        $this->tokenName = 'Data Capture API';
        $this->expiresAt = null;

        Notification::make()
            ->title(__('aho.api_tokens.created'))
            ->success()
            ->send();
    }

    public function revokeToken(int $tokenId): void
    {
        $query = ApiToken::query()->whereKey($tokenId);

        if (! auth()->user()?->canViewAllCountries()) {
            $query->where('user_id', auth()->id());
        }

        $token = $query->firstOrFail();
        $token->forceFill(['revoked_at' => now()])->save();

        Notification::make()
            ->title(__('aho.api_tokens.revoked'))
            ->success()
            ->send();
    }

    /**
     * @return Collection<int, ApiToken>
     */
    public function tokens(): Collection
    {
        $query = ApiToken::query()
            ->with('user')
            ->latest();

        if (! auth()->user()?->canViewAllCountries()) {
            $query->where('user_id', auth()->id());
        }

        return $query->get();
    }

    /**
     * @return array<int, array{method: string, url: string, description: string, group: string}>
     */
    public function endpoints(): array
    {
        $base = url('/api/v1');

        return [
            ['method' => 'GET', 'url' => "{$base}/status", 'description' => __('aho.api_tokens.endpoints.status'), 'group' => 'read'],
            ['method' => 'GET', 'url' => "{$base}/locations", 'description' => __('aho.api_tokens.endpoints.locations'), 'group' => 'read'],
            ['method' => 'GET', 'url' => "{$base}/indicators", 'description' => __('aho.api_tokens.endpoints.indicators'), 'group' => 'read'],
            ['method' => 'GET', 'url' => "{$base}/data-sources", 'description' => __('aho.api_tokens.endpoints.data_sources'), 'group' => 'read'],
            ['method' => 'GET', 'url' => "{$base}/schema/indicator-values", 'description' => __('aho.api_tokens.endpoints.schema'), 'group' => 'read'],
            ['method' => 'GET', 'url' => "{$base}/indicator-values", 'description' => __('aho.api_tokens.endpoints.values_index'), 'group' => 'read'],
            ['method' => 'GET', 'url' => "{$base}/indicator-values/{fact_id}", 'description' => __('aho.api_tokens.endpoints.values_show'), 'group' => 'read'],
            ['method' => 'POST', 'url' => "{$base}/indicator-values", 'description' => __('aho.api_tokens.endpoints.values_store'), 'group' => 'write'],
            ['method' => 'PUT', 'url' => "{$base}/indicator-values/{fact_id}", 'description' => __('aho.api_tokens.endpoints.values_update'), 'group' => 'write'],
            ['method' => 'PATCH', 'url' => "{$base}/indicator-values/{fact_id}", 'description' => __('aho.api_tokens.endpoints.values_patch'), 'group' => 'write'],
            ['method' => 'DELETE', 'url' => "{$base}/indicator-values/{fact_id}", 'description' => __('aho.api_tokens.endpoints.values_delete'), 'group' => 'write'],
        ];
    }

    public function examplePayload(): string
    {
        $values = [
            'numerator_value' => null,
            'denominator_value' => null,
            'value_received' => 72.5,
            'min_value' => null,
            'max_value' => null,
            'target_value' => null,
            'string_value' => null,
            'start_period' => 2025,
            'end_period' => 2025,
            'period' => '2025',
            'categoryoption_id' => $this->firstKey(IndicatorCategory::class, 'categoryoption_id'),
            'datasource_id' => $this->firstKey(DataSource::class, 'datasource_id'),
            'indicator_id' => $this->firstKey(Indicator::class, 'indicator_id'),
            'location_id' => $this->exampleCountryLocationId(),
            'measuremethod_id' => $this->firstKey(MeasureMethod::class, 'measuremethod_id'),
            'approved_by' => null,
            'approved_at' => null,
        ];

        $payload = collect($this->examplePayloadFields())
            ->mapWithKeys(fn (string $field): array => [$field => $values[$field] ?? null])
            ->reject(fn ($value): bool => $value === null)
            ->all();

        return json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @return array<int, string>
     */
    private function examplePayloadFields(): array
    {
        return array_values(array_diff(
            $this->tableColumnNames(HealthIndicatorValue::class),
            ['fact_id', 'date_created', 'date_lastupdated', 'uuid', 'comment', 'user_id', 'priority', 'approval_status'],
        ));
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    private function firstKey(string $modelClass, string $key): int
    {
        try {
            return (int) ($modelClass::query()->orderBy($key)->value($key) ?? 1);
        } catch (Throwable) {
            return 1;
        }
    }

    private function exampleCountryLocationId(): int
    {
        $locationId = UserCountryAccess::locationId();

        if (filled($locationId)) {
            return (int) $locationId;
        }

        return (int) (
            Country::query()
                ->where('locationlevel_id', 2)
                ->orderBy('code')
                ->value('location_id')
            ?? Country::query()
                ->orderBy('location_id')
                ->value('location_id')
            ?? 1
        );
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @return array<int, string>
     */
    private function tableColumnNames(string $modelClass): array
    {
        /** @var Model $model */
        $model = new $modelClass;

        try {
            return DatabaseSchema::connection($model->getConnectionName())->getColumnListing($model->getTable());
        } catch (Throwable) {
            return [
                'uuid',
                'numerator_value',
                'denominator_value',
                'value_received',
                'min_value',
                'max_value',
                'target_value',
                'string_value',
                'start_period',
                'end_period',
                'period',
                'comment',
                'categoryoption_id',
                'datasource_id',
                'indicator_id',
                'location_id',
                'measuremethod_id',
                'user_id',
                'priority',
                'approval_status',
                'approved_by',
                'approved_at',
            ];
        }
    }
}

<?php

namespace App\Filament\Clusters\ApiTokens\Pages;

use App\Filament\Clusters\ApiTokens;
use App\Models\ApiToken;
use App\Support\ApprovalWorkflow;
use App\Support\UserPermissions;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

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
        return json_encode([
            'indicator_id' => 1,
            'location_id' => 38,
            'start_period' => 2025,
            'end_period' => 2025,
            'period' => '2025',
            'categoryoption_id' => 1,
            'datasource_id' => 1,
            'measuremethod_id' => 1,
            'value_received' => 72.5,
            'comment' => ApprovalWorkflow::STATUS_PENDING,
            'priority' => false,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}

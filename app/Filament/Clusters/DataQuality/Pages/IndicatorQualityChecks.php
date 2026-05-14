<?php

namespace App\Filament\Clusters\DataQuality\Pages;

use App\Filament\Clusters\DataQuality;
use App\Support\UserPermissions;
use App\Services\DataQuality\DataQualityService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

class IndicatorQualityChecks extends Page
{
    protected static ?string $cluster = DataQuality::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?string $slug = 'indicator-checks';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.clusters.data-quality.pages.indicator-quality-checks';

    private ?Collection $cachedIssues = null;

    public function getTitle(): string
    {
        return __('aho.resources.indicator_quality_checks.navigation');
    }

    public static function getNavigationLabel(): string
    {
        return __('aho.resources.indicator_quality_checks.navigation');
    }

    public static function canAccess(): bool
    {
        return (bool) auth()->user() && UserPermissions::allowsPage(auth()->user(), static::class);
    }

    /**
     * Get all issues (cached to avoid duplicate queries)
     * 
     * @return Collection<int, array<string, mixed>>
     */
    private function getAllIssues(): Collection
    {
        if ($this->cachedIssues === null) {
            $this->cachedIssues = app(DataQualityService::class)->scanIndicatorValues(250);
        }

        return $this->cachedIssues;
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function getIssues(): Collection
    {
        return $this->getAllIssues()->take(100);
    }

    /**
     * @return array<string, int>
     */
    public function getSummary(): array
    {
        $issues = $this->getAllIssues();

        return [
            'errors' => $issues->where('severity', 'error')->count(),
            'warnings' => $issues->where('severity', 'warning')->count(),
            'checked' => 250,
        ];
    }
}

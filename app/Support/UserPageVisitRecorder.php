<?php

namespace App\Support;

use App\Models\UserPageVisit;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

class UserPageVisitRecorder
{
    private const RECENT_VISIT_SECONDS = 60;

    public function recordRequest(Request $request, int $statusCode): bool
    {
        $path = $request->path();
        $fullUrl = $request->fullUrl();

        if (! $this->shouldRecord($request, $path, $fullUrl, $statusCode, requireHtml: true)) {
            return false;
        }

        $payload = $this->payload($request, $path, $fullUrl);

        app()->terminating(fn () => $this->create($payload));
        $this->rememberRecordedVisit($request, $fullUrl);

        return true;
    }

    public function recordPath(Request $request, string $rawPath): bool
    {
        $normalized = $this->normalizePath($rawPath);

        if ($normalized === null) {
            return false;
        }

        if (! $this->shouldRecord($request, $normalized['path'], $normalized['full_url'], null, requireHtml: false)) {
            return false;
        }

        if (! $this->create($this->payload($request, $normalized['path'], $normalized['full_url']))) {
            return false;
        }

        $this->rememberRecordedVisit($request, $normalized['full_url']);

        return true;
    }

    private function shouldRecord(
        Request $request,
        string $path,
        string $fullUrl,
        ?int $statusCode,
        bool $requireHtml,
    ): bool {
        if (! $request->user()) {
            return false;
        }

        if ($statusCode !== null && ($statusCode < 200 || $statusCode >= 300)) {
            return false;
        }

        if ($statusCode !== null && ! $request->isMethod('GET')) {
            return false;
        }

        if (! Str::startsWith($path, 'admin/')) {
            return false;
        }

        if (Str::contains($path, ['login', 'logout', 'password'])) {
            return false;
        }

        if ($requireHtml && ($request->ajax() || $request->expectsJson() || ! $request->acceptsHtml())) {
            return false;
        }

        return ! $this->wasRecentlyRecorded($request, $fullUrl);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function create(array $payload): bool
    {
        try {
            UserPageVisit::create($payload);

            return true;
        } catch (Throwable $exception) {
            report($exception);

            return false;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Request $request, string $path, string $fullUrl): array
    {
        $user = $request->user();
        $routeCountry = $this->countryRoute($request, $path);

        return [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_email' => $user->email,
            'is_super_admin' => (bool) $user->is_super_admin,
            'location_id' => $user->location_id,
            'country_iso' => ! in_array($routeCountry, ['af', 'global'], true) ? strtoupper($routeCountry) : null,
            'country_name' => null,
            'country_route' => $routeCountry,
            'method' => 'GET',
            'path' => $path,
            'full_url' => $fullUrl,
            'route_name' => $request->route()?->getName(),
            'page_label' => $this->pageLabel($path),
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 1000, ''),
            'visited_at' => now(),
        ];
    }

    private function countryRoute(Request $request, string $path): string
    {
        $routeCountry = (string) ($request->route('country') ?: '');

        if ($routeCountry !== '') {
            return strtolower($routeCountry);
        }

        $segments = explode('/', trim($path, '/'));
        $country = (string) ($segments[1] ?? 'af');

        return preg_match('/^(af|global|[a-z]{2})$/i', $country) === 1 ? strtolower($country) : 'af';
    }

    private function pageLabel(string $path): string
    {
        $segments = array_slice(explode('/', trim($path, '/')), 2);

        if ($segments === []) {
            return 'Dashboard';
        }

        return collect($segments)
            ->map(fn (string $segment): string => (string) Str::of($segment)->replace('-', ' ')->headline())
            ->implode(' / ');
    }

    /**
     * @return array{path: string, full_url: string}|null
     */
    private function normalizePath(string $rawPath): ?array
    {
        $rawPath = trim($rawPath);

        if ($rawPath === '') {
            return null;
        }

        $parts = parse_url($rawPath);
        $path = trim(str_replace('\\', '/', (string) ($parts['path'] ?? $rawPath)), '/');

        if ($path === '') {
            return null;
        }

        $query = isset($parts['query']) && $parts['query'] !== '' ? '?'.$parts['query'] : '';

        return [
            'path' => $path,
            'full_url' => url('/'.$path).$query,
        ];
    }

    private function wasRecentlyRecorded(Request $request, string $fullUrl): bool
    {
        $lastRecordedAt = (int) $request->session()->get($this->recentVisitKey($request, $fullUrl), 0);

        return $lastRecordedAt > 0 && (time() - $lastRecordedAt) < self::RECENT_VISIT_SECONDS;
    }

    private function rememberRecordedVisit(Request $request, string $fullUrl): void
    {
        $request->session()->put($this->recentVisitKey($request, $fullUrl), time());
    }

    private function recentVisitKey(Request $request, string $fullUrl): string
    {
        return 'page-visit.'.sha1($request->user()?->getAuthIdentifier().'|'.$fullUrl);
    }
}

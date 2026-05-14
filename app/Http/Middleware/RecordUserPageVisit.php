<?php

namespace App\Http\Middleware;

use App\Models\UserPageVisit;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RecordUserPageVisit
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if ($this->shouldRecord($request, (int) $response->getStatusCode())) {
            rescue(fn () => UserPageVisit::create($this->payload($request)), report: false);
        }

        return $response;
    }

    private function shouldRecord(Request $request, int $statusCode): bool
    {
        if (! $request->user() || ! $request->isMethod('GET')) {
            return false;
        }

        if ($statusCode < 200 || $statusCode >= 300) {
            return false;
        }

        if (! Str::startsWith($request->path(), 'admin/')) {
            return false;
        }

        if (Str::contains($request->path(), ['login', 'logout', 'password'])) {
            return false;
        }

        if ($request->ajax() || $request->expectsJson() || ! $request->acceptsHtml()) {
            return false;
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Request $request): array
    {
        $user = $request->user();
        $location = $user->location;
        $routeCountry = (string) ($request->route('country') ?: 'global');

        return [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_email' => $user->email,
            'is_super_admin' => (bool) $user->is_super_admin,
            'location_id' => $user->location_id,
            'country_iso' => filled($location?->iso_alpha) ? strtoupper((string) $location->iso_alpha) : null,
            'country_name' => $location?->display_name,
            'country_route' => $routeCountry,
            'method' => $request->method(),
            'path' => $request->path(),
            'full_url' => $request->fullUrl(),
            'route_name' => $request->route()?->getName(),
            'page_label' => $this->pageLabel($request),
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 1000, ''),
            'visited_at' => now(),
        ];
    }

    private function pageLabel(Request $request): string
    {
        $segments = array_slice($request->segments(), 2);

        if ($segments === []) {
            return 'Dashboard';
        }

        return collect($segments)
            ->map(fn (string $segment): string => (string) Str::of($segment)->replace('-', ' ')->headline())
            ->implode(' / ');
    }
}

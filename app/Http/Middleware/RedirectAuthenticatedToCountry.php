<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RedirectAuthenticatedToCountry
{
    public function handle(Request $request, Closure $next)
    {
        // Skip redirect for login and other auth routes
        if (Str::contains($request->path(), ['login', 'logout', 'password'])) {
            return $next($request);
        }

        if ($request->user()) {
            $iso = optional($request->user()->location)->iso_alpha;
            $country = filled($iso) ? strtolower(substr(trim((string) $iso), 0, 2)) : 'global';

            if (! $request->route('country') || $request->route('country') !== $country) {
                $segments = $request->segments();
                $pathAfterCountry = array_slice($segments, 2);

                $redirect = '/admin/'.$country;
                if ($pathAfterCountry !== []) {
                    $redirect .= '/'.implode('/', $pathAfterCountry);
                }

                $queryString = $request->getQueryString();

                return redirect()->to($redirect.($queryString ? "?{$queryString}" : ''));
            }
        }

        return $next($request);
    }
}

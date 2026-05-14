<?php

namespace App\Http\Middleware;

use App\Support\WarehouseLocale;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        app()->setLocale(WarehouseLocale::normalize(
            $request->cookie('locale') ?? $request->session()->get('locale', config('app.locale'))
        ));

        $country = $this->resolveCountry($request);

        if (filled($country)) {
            $request->session()->put('admin_country', $country);

            URL::defaults([
                'country' => $country,
            ]);
        }

        return $next($request);
    }

    private function resolveCountry(Request $request): string
    {
        $country = $request->route('country');

        if (filled($country)) {
            return (string) $country;
        }

        $refererPath = parse_url((string) $request->headers->get('referer'), PHP_URL_PATH);

        if (is_string($refererPath) && preg_match('#/admin/([^/]+)#', $refererPath, $matches) === 1) {
            return $matches[1];
        }

        $sessionCountry = $request->session()->get('admin_country');

        if (filled($sessionCountry)) {
            return (string) $sessionCountry;
        }

        $iso = optional($request->user()?->location)->iso_alpha;

        if (filled($iso)) {
            return strtolower(Str::substr(trim((string) $iso), 0, 2));
        }

        return 'global';
    }
}

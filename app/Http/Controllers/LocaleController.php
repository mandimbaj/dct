<?php

namespace App\Http\Controllers;

use App\Support\WarehouseLocale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function __invoke(Request $request, string $locale): RedirectResponse
    {
        $locale = WarehouseLocale::normalize($locale);

        if (! in_array($locale, WarehouseLocale::codes(), true)) {
            abort(400);
        }

        $request->session()->put('locale', $locale);
        app()->setLocale($locale);

        return redirect()
            ->back()
            ->withCookie(cookie('locale', $locale, 525600, null, null, null, true, false, 'lax'));
    }
}

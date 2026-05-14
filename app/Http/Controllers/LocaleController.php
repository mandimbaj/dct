<?php

namespace App\Http\Controllers;

use App\Support\WarehouseLocale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LocaleController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'locale' => ['required', Rule::in(WarehouseLocale::codes())],
        ]);

        $locale = WarehouseLocale::normalize($data['locale']);

        $request->session()->put('locale', $locale);
        app()->setLocale($locale);

        return redirect()
            ->back()
            ->withCookie(cookie('locale', $locale, 525600, null, null, null, true, false, 'lax'));
    }
}

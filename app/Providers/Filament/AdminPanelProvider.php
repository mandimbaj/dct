<?php

namespace App\Providers\Filament;

use App\Filament\Resources\UserPageVisits\UserPageVisitResource;
use App\Http\Middleware\RedirectAuthenticatedToCountry;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use App\Support\AhoBrand;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        Route::bind('country', function ($value) {
            return $value ?: 'global';
        });

        return $panel
            ->default()
            ->id('admin')
            ->path('admin/{country}')
            ->login()
            ->homeUrl(fn (): string => Auth::check() ? URL::to('/admin/'.(optional(Auth::user()->location)->iso_alpha ? strtolower(substr(trim((string) Auth::user()->location->iso_alpha), 0, 2)) : 'global')) : URL::to('/admin/global'))
            ->brandName(fn (): string => AhoBrand::appName())
            ->brandLogo(fn (): string => asset(AhoBrand::logoPath()))
            ->darkModeBrandLogo(fn (): string => asset(AhoBrand::logoPath()))
            ->brandLogoHeight('2.75rem')
            ->sidebarWidth('14rem')
            ->maxContentWidth(Width::Full)
            ->spa()
            ->globalSearch()
            ->globalSearchDebounce('700ms')
            ->colors([
                'primary' => Color::hex('#0093D5'),
            ])
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): HtmlString => new HtmlString(
                    '<link rel="icon" href="'.asset('favicon.ico').'" sizes="any">'.
                    '<link rel="icon" type="image/png" href="'.asset('favicon.png').'">'.
                    '<link rel="stylesheet" href="'.asset('css/who-afro-filament.css').'?v=20260525-1">'.
                    '<script defer src="'.asset('js/aho-sidebar-tooltips.js').'?v=20260512-3"></script>'
                ),
            )
            ->renderHook(
                PanelsRenderHook::SIMPLE_PAGE_START,
                fn () => view('filament.language-switcher'),
            )
            ->renderHook(
                PanelsRenderHook::TOPBAR_START,
                fn () => view('filament.topbar-brand'),
            )
            ->renderHook(
                PanelsRenderHook::TOPBAR_END,
                fn () => view('filament.topbar-actions'),
            )
            ->renderHook(
                PanelsRenderHook::FOOTER,
                fn () => view('filament.footer'),
            )
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn () => view('filament.navigation-assistant'),
            )
            ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\Filament\Clusters')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->resources([
                UserPageVisitResource::class,
            ])
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                SetLocale::class,
                RedirectAuthenticatedToCountry::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                SecurityHeaders::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}

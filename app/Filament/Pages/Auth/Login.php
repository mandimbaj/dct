<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Schemas\Components\Component;
use Illuminate\Contracts\Support\Htmlable;

class Login extends BaseLogin
{
    public function getFormContentComponent(): Component
    {
        return parent::getFormContentComponent()
            ->visible(fn (): bool => (bool) config('services.microsoft_entra.local_login_enabled', true));
    }

    public function getSubheading(): string|Htmlable|null
    {
        if (
            config('services.microsoft_entra.enabled')
            && ! config('services.microsoft_entra.local_login_enabled', true)
        ) {
            return __('aho.microsoft_entra.local_login_disabled_subheading');
        }

        return parent::getSubheading();
    }
}

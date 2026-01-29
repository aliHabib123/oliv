<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Illuminate\Contracts\Support\Htmlable;

class Login extends BaseLogin
{
    public function getHeading(): string | Htmlable
    {
        return '';
    }

    public function getFooter(): ?Htmlable
    {
        return view('filament.pages.auth.login-footer');
    }
}

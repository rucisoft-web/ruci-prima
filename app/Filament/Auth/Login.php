<?php

namespace App\Filament\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Illuminate\Contracts\Support\Htmlable;

/** Halaman masuk Ruci PRIMA; pilihan masuk sebagai tamu ditambahkan lewat render hook di bawah formulir. */
class Login extends BaseLogin
{
    public function getHeading(): string|Htmlable|null
    {
        return filled($this->userUndertakingMultiFactorAuthentication) ? parent::getHeading() : 'Masuk ke Ruci PRIMA';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return filled($this->userUndertakingMultiFactorAuthentication)
            ? parent::getSubheading()
            : 'Gunakan akun yang diberikan administrator perusahaan Anda.';
    }
}

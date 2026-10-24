<?php

namespace App\Http\Controllers;

use App\Filament\App\Pages\DasborPimpinan;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Masuk sebagai tamu (hanya baca) ke salah satu perusahaan contoh, tanpa kata sandi. */
class GuestLoginController
{
    public const DEFAULT_COMPANY = 'sahid';

    public function __invoke(Request $request): RedirectResponse
    {
        $guest = User::guestAccount();
        abort_unless($guest, 404);

        $companies = $guest->getTenants(filament()->getPanel('app'));
        $company = $companies->firstWhere('slug', (string) $request->query('perusahaan', self::DEFAULT_COMPANY))
            ?? $companies->firstWhere('slug', self::DEFAULT_COMPANY)
            ?? $companies->first();
        abort_unless($company, 404);

        if (Auth::id() !== $guest->id) {
            Auth::login($guest);
            $request->session()->regenerate();
        }

        return redirect(DasborPimpinan::getUrl(panel: 'app', tenant: $company));
    }
}

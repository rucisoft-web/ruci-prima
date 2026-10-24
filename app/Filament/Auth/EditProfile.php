<?php

namespace App\Filament\Auth;

use Filament\Auth\Pages\EditProfile as BaseEditProfile;

/** Profil pengguna; akun tamu bersama tidak boleh membukanya. */
class EditProfile extends BaseEditProfile
{
    public function mount(): void
    {
        if (auth()->user()?->isGuest()) {
            $this->redirect(filament()->getUrl());

            return;
        }

        parent::mount();
    }
}
